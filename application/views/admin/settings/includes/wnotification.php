<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<h4 class="tw-font-semibold tw-mt-0 tw-text-neutral-800">Whatsapp Notifcation Configuration</h4>
<hr class="hr-panel-heading" />

<?php
echo render_input('settings[wn_userID]', 'User ID', get_option('wn_userID'));
echo render_input('settings[wn_deviceName]', 'Device Name ', get_option('wn_deviceName'));

?>
