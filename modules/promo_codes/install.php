<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$db_prefix = db_prefix();

$table_promo_codes = $db_prefix . 'promo_codes';
if (!$CI->db->table_exists($table_promo_codes)) {
    // --Type: fixed or percentage, Status: -- active or inactive
    $CI->db->query("CREATE TABLE `$table_promo_codes` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `code` VARCHAR(150) NOT NULL UNIQUE,
        `type` VARCHAR(50) NOT NULL, -- e.g., 'fixed', 'percentage'
        `amount` DECIMAL(10, 2) NOT NULL,
        `usage_limit` INT DEFAULT 0,
        `start_date` DATE NOT NULL,
        `end_date` DATE NOT NULL,
        `status` VARCHAR(50) DEFAULT 'inactive', -- e.g., 'active', 'inactive'
        `metadata` text COMMENT 'Extra data such as condition e.t.c',
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");
}

$table_promo_usage = $db_prefix . 'promo_codes_usage';
$table_client = $db_prefix . 'clients';
if (!$CI->db->table_exists($table_promo_usage)) {
    $CI->db->query("CREATE TABLE `$table_promo_usage` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `promo_code_id` INT UNSIGNED NOT NULL,
        `user_id` INT NOT NULL,
        `rel_type` VARCHAR(150) NOT NULL DEFAULT 'invoice' COMMENT 'the entity on which the code is applied',
        `rel_id` VARCHAR(150) NOT NULL,
        `value` VARCHAR(150) NOT NULL,
        `currency` VARCHAR(150) NOT NULL,
        `used_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        FOREIGN KEY (`promo_code_id`) REFERENCES `" . $table_promo_codes . '`(`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_' . $table_promo_usage . '_user_id` FOREIGN KEY (`user_id`) REFERENCES `' . $table_client . '`(`userid`) ON DELETE CASCADE            
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
}

/**
 * Patches the proposal HTML file to insert the missing hook before the total-to-words section.
 *
 * This function will read the proposal file, locate the `get_option('total_to_words_enabled')` call,
 * and insert the hook right before the PHP opening tag (`<?php`).
 *
 * @return bool True if the patch was successfully applied, false otherwise.
 */
function promo_codes_patch_proposal_view_file_with_hook()
{
    $file_path = VIEWPATH . 'themes/' . active_clients_theme() . '/views/viewproposal.php';
    // Ensure the file exists before proceeding
    if (!file_exists($file_path)) {
        return false;
    }

    // Get the current content of the file
    $file_content = file_get_contents($file_path);
    if (strpos($file_content, 'after_total_summary_proposalhtml') !== false) {
        return true;
    }

    // Regular expression to find the first occurrence of `get_option('total_to_words_enabled')` or `get_option("total_to_words_enabled")`
    if (preg_match('/get_option\s*\(\s*[\'"]total_to_words_enabled[\'"]\s*\)/', $file_content, $matches, PREG_OFFSET_CAPTURE)) {
        // Get the position of the match in the file content
        $position = $matches[0][1];

        // Find the position of the last PHP opening tag before this match
        $php_open_tag_pos = strrpos(substr($file_content, 0, $position), '<?php');

        if ($php_open_tag_pos !== false) {
            // Prepare the hook content to be inserted before the PHP tag
            $hook = '<?/php hooks()->do_action(\'after_total_summary_proposalhtml\', $proposal); ?/>';
            $hook = str_replace('?/', '?', $hook) . PHP_EOL;

            // Insert the hook just before the PHP opening tag
            $file_content = substr_replace($file_content, $hook, $php_open_tag_pos, 0); // Insert at the PHP opening position

            // Write the modified content back to the file
            return file_put_contents($file_path, $file_content) !== false;
        }
    }

    // If we couldn't find the required pattern or PHP tag, return false
    return false;
}
promo_codes_patch_proposal_view_file_with_hook();