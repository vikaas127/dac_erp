<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Normalize phone to E.164-ish (best-effort) without leading +
 */
if (!function_exists('wn_normalize_phone')) {
    function wn_normalize_phone($phone)
    {
        $phone = trim($phone);
        // remove spaces, dashes, parentheses, plus signs
        $phone = preg_replace('/[^\d]/', '', $phone);
        return $phone;
    }
}

/**
 * Core sender for file messages.
 * @param string $phone    e.g., "918233081931"
 * @param string $message  e.g., "Please check your file"
 * @param string $filePath absolute path to the file on disk
 * @return array ['success'=>bool, 'status'=>int|null, 'response'=>mixed, 'error'=>string|null]
 */
if (!function_exists('wn_send_whatsapp_file')) {
    function wn_send_whatsapp_file($phone, $message, $filePath)
    {
        $CI =& get_instance();

        // Read from tbloptions
        $token      = get_option('wn_userID');      // your token (e.g., ce3415....)
        $deviceName = get_option('wn_deviceName');  // e.g., VikasYadav

        if (!$token || !$deviceName) {
            $err = 'WhatsApp settings missing: wn_userID and/or wn_deviceName.';
            if (function_exists('log_activity')) { log_activity($err); }
            return ['success'=>false, 'status'=>null, 'response'=>null, 'error'=>$err];
        }

        $endpoint = "https://messagesapi.co.in/chat/sendMessageFile/{$token}/{$deviceName}";

        $phone   = wn_normalize_phone($phone);
        $message = (string) $message;

        if (!is_file($filePath)) {
            $err = 'Attachment file not found: ' . $filePath;
            if (function_exists('log_activity')) { log_activity($err); }
            return ['success'=>false, 'status'=>null, 'response'=>null, 'error'=>$err];
        }

        $real = realpath($filePath);
        if ($real === false) { $real = $filePath; }

        // Prepare multipart form-data
        $postFields = [
            'phone'   => $phone,
            'message' => $message,
            'file'    => new CURLFile($real, mime_content_type($real) ?: 'application/octet-stream', basename($real)),
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $endpoint,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postFields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            // If your server lacks proper CA bundle and you get SSL errors, you can flip the next two to false (not recommended).
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $raw    = curl_exec($ch);
        $errno  = curl_errno($ch);
        $errstr = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            $msg = "WhatsApp API cURL error ({$errno}): {$errstr}";
            if (function_exists('log_activity')) { log_activity($msg); }
            return ['success'=>false, 'status'=>null, 'response'=>null, 'error'=>$msg];
        }

        // Try decode JSON if possible
        $decoded = null;
        if (is_string($raw) && strlen($raw)) {
            $tmp = json_decode($raw, true);
            $decoded = (json_last_error() === JSON_ERROR_NONE) ? $tmp : $raw;
        }

        $success = ($status >= 200 && $status < 300);
        if (!$success && function_exists('log_activity')) {
            log_activity('WhatsApp API returned status '.$status.'; response: '.(is_string($decoded) ? $decoded : json_encode($decoded)));
        }

        return [
            'success'  => $success,
            'status'   => $status,
            'response' => $decoded,
            'error'    => $success ? null : 'Non-2xx response from WhatsApp API',
        ];
    }
}

/**
 * Optional: Send text-only message (no file)
 */


if (!function_exists('wn_send_whatsapp_text')) {
    /**
     * Send a WhatsApp TEXT message via messagesapi.co.in (JSON endpoint).
     * Uses tbloptions:
     *  - wn_userID      => token/id
     *  - wn_deviceName  => device/name
     *  - wn_api_key     => (optional) x-api-key header
     *
     * @return array ['success'=>bool,'status'=>int|null,'response'=>mixed,'error'=>string|null]
     */
    function wn_send_whatsapp_text($phone, $message)
    {
        //log_message('info', 'WhatsApp text sent to ' . $phone);
        // Read settings from tbloptions
        $token      = function_exists('get_option') ? get_option('wn_userID') : null;
        $deviceName = function_exists('get_option') ? get_option('wn_deviceName') : null;
        $apiKey     = function_exists('get_option') ? get_option('wn_api_key') : null; // optional

        if (!$token || !$deviceName) {
            $err = 'WhatsApp settings missing: wn_userID and/or wn_deviceName.';
            if (function_exists('og_message'))     og_message('error', $err);
            if (function_exists('log_activity'))   log_activity($err);
            if (function_exists('set_alert'))      set_alert('warning', $err);
            return ['success'=>false, 'status'=>null, 'response'=>null, 'error'=>$err];
        }

        // Normalize & validate
        if (function_exists('wn_normalize_phone')) {
            $phone = wn_normalize_phone($phone);
        } else {
            $phone = preg_replace('/[^\d]/', '', (string)$phone);
        }
        $message = (string)$message;

        if ($phone === '' || $message === '') {
            $err = 'Phone or message is empty.';
            if (function_exists('og_message'))   og_message('error', $err);
            return ['success'=>false, 'status'=>null, 'response'=>null, 'error'=>$err];
        }

        // Build request
        $endpoint = 'https://messagesapi.co.in/chat/sendMessage';
        $payload  = [
            'id'      => $token,
            'name'    => $deviceName,
            'phone'   => $phone,
            'message' => $message,
        ];
        $headers  = ['Content-Type: application/json', 'Accept: application/json'];
        if (!empty($apiKey)) $headers[] = 'x-api-key: ' . $apiKey;

        // cURL
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $endpoint,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $raw    = curl_exec($ch);
        $errno  = curl_errno($ch);
        $errstr = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            $msg = "WhatsApp API cURL error ({$errno}): {$errstr}";
            if (function_exists('og_message'))   og_message('error', $msg);
            if (function_exists('log_activity')) log_activity($msg);
            return ['success'=>false, 'status'=>null, 'response'=>null, 'error'=>$msg];
        }

        // Decode response (if JSON)
        $decoded = null;
        if (is_string($raw) && $raw !== '') {
            $tmp = json_decode($raw, true);
            $decoded = (json_last_error() === JSON_ERROR_NONE) ? $tmp : $raw;
        }

        $success = ($status >= 200 && $status < 300);
        if ($success) {
            if (function_exists('log_message'))   log_message('info', 'WhatsApp text sent to ' . $phone);
            if (function_exists('log_activity')) log_activity('WhatsApp text sent to ' . $phone);
            if (function_exists('set_alert'))    set_alert('success', 'WhatsApp text sent to ' . $phone);
        } else {
            if (function_exists('log_message'))   log_message('error', "WhatsApp text failed to {$phone} (HTTP {$status})");
            if (function_exists('log_activity')) log_activity('WhatsApp API returned status '.$status.'; response: '.(is_string($decoded) ? $decoded : json_encode($decoded)));
        }

        return [
            'success'  => $success,
            'status'   => $status,
            'response' => $decoded,
            'error'    => $success ? null : 'Non-2xx response from WhatsApp API',
        ];
    }
}
function whatsapp_template($class)
{
    $CI = &get_instance();

    $params = func_get_args();

    // First params is the $class param
    unset($params[0]);

    $params = array_values($params);

    $path = get_whatsapp_template_path($class, $params);
    if (!file_exists($path)) {
        if (!defined('CRON')) {
            show_error('Mail Class Does Not Exists [' . $path . ']');
        } else {
            return false;
        }
    }

    // Include the mailable class
    if (!class_exists($class, false)) {
        include_once($path);
    }

    // Initialize the class and pass the params
    $instance = new $class(...$params);
    // Call the send method
    return $instance;
}

function get_whatsapp_template_path($class, &$params)
{
    $CI  = &get_instance();
    $dir = APPPATH . 'libraries/whatsapp/';

    // Check if second parameter is module and is activated so we can get the class from the module path
    // Also check if the first value is not equal to '/' e.q. when import is performed we set
    // for some values which are blank to "/"
    if (isset($params[0]) && is_string($params[0]) && $params[0] !== '/' && is_dir(module_dir_path($params[0]))) {
        $module = $CI->app_modules->get($params[0]);

        if ($module['activated'] === 1) {
            $dir = module_libs_path($params[0]) . 'whatsapp/';
        }

        unset($params[0]);
        $params = array_values($params);
    }

    return $dir . ucfirst($class) . '.php';
}

if (!function_exists('get_whatsapp_message_by_template')) {
    function get_whatsapp_message_by_template($template_name, $phone_number, $data) {
        // Fetch the WhatsApp message template using the template name (slug)
        $tempee = whatsapp_template($template_name);
        printr($tempee);
        die();
        $CI =& get_instance();
        $CI->db->select('message');
        $CI->db->from('tblemailtemplates');
        $CI->db->where('slug', $template_name);
        $CI->db->where('language', 'english');  // You can adjust this based on the language if needed
        $template = $CI->db->get()->row();
        //print_r($message);
        echo "<pre>";
       // print_r($data);
      //  print_r($template->message);
      //  die();
        // If the template does not exist
        if (!$template || !isset($template->message)) {
            return 'Template not found.';
        }

        // Get the WhatsApp message template
        $message = $template->message;

        // Replace placeholders with actual data
        foreach ($data as $key => $value) {
            // Replace {key} with the corresponding value
            $message = str_replace('{' . $key . '}', $value, $message);
        }

        // Optionally, you can add the phone number to the message as well if needed
        $message = str_replace('{phone_number}', $phone_number, $message);
        
        return $message;
    }
}