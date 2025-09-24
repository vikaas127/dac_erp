<?php

defined('BASEPATH') or exit('No direct script access allowed');

class App_whatsapp_template
{
    public $slug = '';
    public $send_to;
    public $merge_fields = [];
    public $attachments = [];
    public $rel_id;
    public $rel_type;
    public $staff_id;
    protected $ci;
    protected $template;
    protected $for;

    public function __construct()
    {
        $this->ci = &get_instance();
    }
/**
 * Convert simple HTML to WhatsApp-friendly plain text:
 * - <b>, <strong> -> *bold*
 * - <i>, <em> -> _italic_
 * - <u> -> _ (underline not supported by WA officially, we use italic fallback)
 * - <br>, <p> -> newlines
 * - Removes remaining HTML tags
 */
function html_to_whatsapp_text($html)
{
    if ($html === null) return '';

    // Normalize line breaks
    $html = str_replace(["\r\n","\r"], "\n", $html);

    // Replace <br> and block elements with newlines
    $html = preg_replace('#<(br|br\/|p|div|li|tr|h[1-6])\s*\/?>#i', "\n", $html);

    // Bold
    $html = preg_replace('#<(b|strong)>(.*?)<\/(b|strong)>#is', '*$2*', $html);

    // Italic
    $html = preg_replace('#<(i|em)>(.*?)<\/(i|em)>#is', '_$2_', $html);

    // Underline -> fallback to italic
    $html = preg_replace('#<u>(.*?)<\/u>#is', '_$1_', $html);

    // Strikethrough
    $html = preg_replace('#<s>(.*?)<\/s>#is', '~$1~', $html);

    // Links: convert <a href="url">text</a> -> text (url)
    $html = preg_replace_callback('#<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>#is', function($m){
        $url = trim($m[1]);
        $text = trim(strip_tags($m[2]));
        if ($text === '') return $url;
        return $text . ' (' . $url . ')';
    }, $html);

    // Remove any remaining tags but keep contents
    $text = strip_tags($html);

    // Decode HTML entities
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Collapse multiple blank lines
    $text = preg_replace("/\n{3,}/", "\n\n", $text);

    // Trim
    $text = trim($text);

    return $text;
}

    public function send()
    {
        if (!method_exists($this, 'build')) {
            show_error('WhatsApp template class "' . get_class($this) . '" must contain "build" method.');
        }

        $this->build();

        // ✅ Load WhatsApp template from DB (using slug)
        $this->template = $this->prepare();

        if (!$this->template) {
            log_activity('WhatsApp template not found: ' . $this->slug);
            $this->clear();
            return false;
        }

        // Parse placeholders
        $this->template = parse_email_template($this->template, $this->merge_fields);
        $message = $this->template->message;
      $message = html_to_whatsapp_text($message);
        // ✅ Decide whether to send text-only or file+text
        $sent = false;

        if (!empty($this->attachments)) {
            foreach ($this->attachments as $att) {
                if (is_string($att)) {
                    // In case only file path was added
                    $sent = wn_send_whatsapp_file($this->send_to, $message, $att);
                } elseif (isset($att['attachment'])) {
                    $sent = wn_send_whatsapp_file($this->send_to, $message, $att['attachment']);
                }
            }
        } else {
            $sent = wn_send_whatsapp_text($this->send_to, $message);
        }

        if (is_array($sent) && $sent['success']) {
            log_activity('WhatsApp sent to [' . $this->send_to . ', Template: ' . $this->template->name . ']');
            $this->clear();
            return true;
        }

        log_activity('Failed to send WhatsApp to ' . $this->send_to);
        $this->clear();
        return false;
    }

    public function to($phone)
    {
        $this->send_to = wn_normalize_phone($phone);
        return $this;
    }

    public function set_merge_fields($fields, ...$params)
    {
        if (!is_array($fields)) {
            $fields = $this->ci->app_merge_fields->format_feature($fields, ...$params);
        }
        $this->merge_fields = array_merge($this->merge_fields, $fields);
        return $this;
    }

    public function add_attachment($attachment)
    {
        $this->attachments[] = $attachment;
        return $this;
    }

    public function set_rel_id($rel_id)
    {
        $this->rel_id = $rel_id;
        return $this;
    }

    public function set_rel_type($rel_type)
    {
        $this->rel_type = $rel_type;
        return $this;
    }

    public function set_staff_id($id)
    {
        $this->staff_id = $id;
        return $this;
    }

    private function clear()
    {
        $this->attachments = [];
        $this->staff_id    = null;
        $this->rel_type    = null;
        $this->rel_id      = null;
    }

    protected function prepare($phone = null, $template = null, $params = [])
    {
        $slug  = $this->slug;
        $phone = $phone === null ? $this->send_to : $phone;

        $language = get_option('active_language');

        $this->ci->db->where('slug', $slug);
        $this->ci->db->where('language', $language);
        $this->ci->db->where('channel', 'whatsapp'); // ✅ filter by whatsapp
        $template = $this->ci->db->get(db_prefix().'emailtemplates')->row();

        if (!$template) {
            $this->ci->db->where('slug', $slug);
            $this->ci->db->where('language', 'english');
            $this->ci->db->where('channel', 'whatsapp');
            $template = $this->ci->db->get(db_prefix().'emailtemplates')->row();
        }

        return $template;
    }
}
