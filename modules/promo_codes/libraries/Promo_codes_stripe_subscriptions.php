<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Promo_codes_stripe_subscriptions extends Stripe_subscriptions
{
    /**
     * Subscribe a customer to a Stripe subscription while applying any valid promo codes.
     * 
     * @param string $customer_id Stripe customer ID.
     * @param array  $params      Parameters for creating the Stripe subscription, 
     *                            must include 'metadata' with 'pcrm-subscription-hash'.
     *
     * @return \Stripe\Subscription|false The created Stripe subscription object on success, or false on failure.
     *
     * @throws \Throwable If an error occurs during the process.
     */
    public function subscribe($customer_id, $params = [])
    {
        $CI = &get_instance();
        $CI->load->model(PROMO_CODES_MODULE_NAME . '/promo_codes_model');
        try {
            $CI->db->trans_start();

            $subscription_hash = $params['metadata']['pcrm-subscription-hash'] ?? null;
            if (!$subscription_hash) {
                throw new \InvalidArgumentException('Missing subscription hash in metadata.');
            }

            $session_key = 'promo_codes_subscription_' . $subscription_hash . '_promo_codes';
            $promo_codes = $CI->session->userdata($session_key);
            $sales_object = $CI->subscriptions_model->get_by_hash($subscription_hash);

            if (!empty($promo_codes) && $sales_object) {
                $discounts = [];
                foreach ($promo_codes as $code) {
                    $promo = $CI->promo_codes_model->get_by_code($code);
                    if (!$promo || empty($promo->metadata['stripe_coupon_id'])) {
                        continue;
                    }

                    $logged = $CI->promo_codes_model->log_usage(
                        $promo->id,
                        $sales_object->id,
                        'subscription',
                        $sales_object->clientid
                    );

                    if (!$logged) {
                        throw new \RuntimeException("Failed to log promo code usage for code: {$code}");
                    }

                    $discounts[] = ['coupon' => $promo->metadata['stripe_coupon_id']];
                }

                if (!empty($discounts)) {
                    $params['discounts'] = $discounts;
                }
            }

            $stripe_subscription = parent::subscribe($customer_id, $params);
            if ($stripe_subscription) {

                $CI->session->unset_userdata($session_key);
                $CI->db->trans_complete();

                if ($CI->db->trans_status() === FALSE) {
                    log_message('error', 'Subscription created on Stripe, but failed to commit local database transaction for subscription hash: ' . $subscription_hash);
                }
            } else {
                $CI->db->trans_rollback();
            }

            return $stripe_subscription;
        } catch (\Throwable $th) {
            $CI->db->trans_rollback();
            throw $th;
        }
    }

    /**
     * Retrieve a Stripe subscription, using CodeIgniter session cache with expiry.
     *
     * This method attempts to fetch the subscription data from the session cache first.
     * If the cached data exists and is still valid within the provided expiration time,
     * it is returned immediately. Otherwise, a fresh request is made to the Stripe API,
     * the result is cached in the session with a timestamp, and then returned.
     *
     * @param string $stripe_subscription_id The ID of the Stripe subscription.
     * @param int    $expire_minutes          The number of minutes before the cached data expires. Defaults to 15 minutes.
     *
     * @return \Stripe\Subscription|null Returns the Stripe subscription object if successful, or null on failure.
     */
    public function get_cached_stripe_subscription($stripe_subscription_id, $expire_minutes = 15)
    {
        $CI = &get_instance();

        $cacheKey = 'stripe_subscription_' . $stripe_subscription_id;
        $cache = $CI->session->userdata($cacheKey);

        if ($cache && isset($cache['data'], $cache['timestamp'])) {
            $age = time() - $cache['timestamp'];
            if ($age <= ($expire_minutes * 60)) {
                return $cache['data'];
            }
            // Expired — remove
            $CI->session->unset_userdata($cacheKey);
        }

        // Fetch fresh
        try {

            $stripeSub = \Stripe\Subscription::retrieve($stripe_subscription_id);

            // Cache with timestamp
            $CI->session->set_userdata($cacheKey, [
                'data'      => $stripeSub,
                'timestamp' => time()
            ]);

            return $stripeSub;
        } catch (Exception $e) {
            log_message('error', 'Stripe API Error: ' . $e->getMessage());
            return null;
        }
    }
}