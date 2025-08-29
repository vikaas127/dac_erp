<!--<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="mtop40">
    <div class="col-md-4 col-md-offset-4 text-center">
        <h1 class="tw-font-semibold mbot20 login-heading">
            <?php
         echo _l(get_option('allow_registration') == 1 ? 'clients_login_heading_register' : 'clients_login_heading_no_register');
         ?>
        </h1>
    </div>
    <div class="col-md-4 col-md-offset-4 col-sm-8 col-sm-offset-2">
        <?php echo form_open($this->uri->uri_string(), ['class' => 'login-form']); ?>
        <?php hooks()->do_action('clients_login_form_start'); ?>
        <div class="panel_s">
            <div class="panel-body">

                <?php if (!is_language_disabled()) { ?>
                <div class="form-group">
                    <label for="language" class="control-label"><?php echo _l('language'); ?>
                    </label>
                    <select name="language" id="language" class="form-control selectpicker"
                        onchange="change_contact_language(this)"
                        data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>"
                        data-live-search="true">
                        <?php $selected = (get_contact_language() != '') ? get_contact_language() : get_option('active_language'); ?>
                        <?php foreach ($this->app->get_available_languages() as $availableLanguage) { ?>
                        <option value="<?php echo e($availableLanguage); ?>"
                            <?php echo ($availableLanguage == $selected) ? 'selected' : '' ?>>
                            <?php echo e(ucfirst($availableLanguage)); ?>
                        </option>
                        <?php } ?>
                    </select>
                </div>
                <?php } ?>

                <div class="form-group">
                    <label for="email"><?php echo _l('clients_login_email'); ?></label>
                    <input type="text" autofocus="true" class="form-control" name="email" id="email">
                    <?php echo form_error('email'); ?>
                </div>

                <div class="form-group">
                    <label for="password"><?php echo _l('clients_login_password'); ?></label>
                    <input type="password" class="form-control" name="password" id="password">
                    <?php echo form_error('password'); ?>
                </div>

                <?php if (show_recaptcha_in_customers_area()) { ?>
                <div class="g-recaptcha tw-mb-4" data-sitekey="<?php echo get_option('recaptcha_site_key'); ?>"></div>
                <?php echo form_error('g-recaptcha-response'); ?>
                <?php } ?>

                <div class="checkbox">
                    <input type="checkbox" name="remember" id="remember">
                    <label for="remember">
                        <?php echo _l('clients_login_remember'); ?>
                    </label>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">
                        <?php echo _l('clients_login_login_string'); ?>
                    </button>
                    <?php if (get_option('allow_registration') == 1) { ?>
                    <a href="<?php echo site_url('authentication/register'); ?>" class="btn btn-success btn-block">
                        <?php echo _l('clients_register_string'); ?>
                    </a>
                    <?php } ?>
                </div>
                <a href="<?php echo site_url('authentication/forgot_password'); ?>">
                    <?php echo _l('customer_forgot_password'); ?>
                </a>
                <?php hooks()->do_action('clients_login_form_end'); ?>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>

-->
<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<style>
    header {
        display: none !important;
    }
    footer.footer {
    display: none !important;
    }
    body{
        background: none;
    }

    .container{
        width:100%;
    }
    div#wrapper {
        margin: 0px !important;
        padding: 0px !important;
    }
    .header{display: none}
    .company-logo {
        width: 250px;
        margin: 0 auto;
        padding: 25px 10px;
    }
    .login-form h1 {
        margin-top: 0;
        font-family: 'Poppins';
        margin-bottom: 35px;
        font-size: 20px;
    }

    div#content {
        padding:0 !important;
    }
    .login-form .panel-body {
        background: #012947;
        border-radius: 10px !important
    }
    .login-form .panel-body h1{
        color: #fff;
    }
    .login-form .panel-body label{
        color: #fff;
        font-weight: 300;
        font-family: 'Poppins';
    }
    .login-form .panel-body input, .login-form .panel-body select{
        outline: none !important;
        box-shadow: none !important;
        border: 1px solid #fff !important
    }
    .login-form .panel-body input:focus, .login-form .panel-body select:focus {
        border-color: #fff !important
    }
    .login-form .panel-body a {
        color: #39c529;
        font-family: 'Poppins';
    }
    .login-form .panel-body .checkbox{
        margin: 0;
    }
    .login-form .panel-body .checkbox label {
        color: #fff;
        font-weight: 300;
        font-family: 'Poppins';
    }

    .tab-buttons {
        display: flex;
        margin-bottom: 15px;
        gap: 5px;
    }
    .tab-buttons a {
        color: #fff !important;
        font-weight: 500;
        width: 50%;
        display: inline-block;
        padding: 10px 15px;
        text-align: center;
        border-bottom: 1px solid transparent;
        opacity: 0.5;
    }
    .tab-buttons a.active {
        opacity: 1;
        border-color: #fff;
    }

    .right-box{
        padding: 0;
        display: flex;
    }

    .carousel, .carousel-inner{
        display: flex
    }

    footer{
        margin-top: 0
    }

    .align-items-center{
        align-items: center
    }

    .slides{
        align-items: center;
    }




    @media (min-width: 992px) {
        .row{
            display: flex
        }
        .right-box {
            min-height: calc(100vh - 50px);
        }
        .left-box{padding-right: 30px}
    }
    @media (min-width: 1200px) {
        .left-box{padding-right: 50px}
    }
	
	
	

</style>

<div class="col-md-5 col-lg-4 mtop40 left-box">
    <div class="company-logo ">
        <?php get_dark_company_logo(); ?>
    </div>
   
    <div class="login-form">
        <?php echo form_open($this->uri->uri_string(), ['class' => 'login-form']); ?>
        <?php hooks()->do_action('clients_login_form_start'); ?>
        <div class="panel_s">
            <div class="panel-body">
                <h1 class="tw-font-semibold login-heading text-center">
                    <?php
                    echo _l(get_option('allow_registration') == 1 ? 'clients_login_heading_register' : 'clients_login_heading_no_register');
                    ?>
                </h1>
                <div class="d-flex tab-buttons">
                    <a href="<?php echo site_url('admin/authentication'); ?>" class="btn-link">Company</a>
                    <a href="<?php echo site_url('authentication/login'); ?>" class="btn-link active">Vendor</a>
                </div>
                <?php if (!is_language_disabled()) { ?>
                <div class="form-group">
                    <label for="language" class="control-label"><?php echo _l('language'); ?>
                    </label>
                    <select name="language" id="language" class="form-control selectpicker"
                        onchange="change_contact_language(this)"
                        data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>"
                        data-live-search="true">
                        <?php $selected = (get_contact_language() != '') ? get_contact_language() : get_option('active_language'); ?>
                        <?php foreach ($this->app->get_available_languages() as $availableLanguage) { ?>
                        <option value="<?php echo e($availableLanguage); ?>"
                            <?php echo ($availableLanguage == $selected) ? 'selected' : '' ?>>
                            <?php echo e(ucfirst($availableLanguage)); ?>
                        </option>
                        <?php } ?>
                    </select>
                </div>
                <?php } ?>

                <div class="form-group">
                    <label for="email"><?php echo _l('clients_login_email'); ?></label>
                    <input type="text" autofocus="true" class="form-control" name="email" id="email" placeholder="Enter Email Address">
                    <?php echo form_error('email'); ?>
                </div>

                <div class="form-group">
                    <label for="password"><?php echo _l('clients_login_password'); ?></label>
                    <input type="password" class="form-control" name="password" id="password" placeholder="Enter Password">
                    <?php echo form_error('password'); ?>
                </div>

                <?php if (show_recaptcha_in_customers_area()) { ?>
                <div class="g-recaptcha tw-mb-4" data-sitekey="<?php echo get_option('recaptcha_site_key'); ?>"></div>
                <?php echo form_error('g-recaptcha-response'); ?>
                <?php } ?>

                <div class="row align-items-center form-group">
                    <div class="col-sm-6">
                        <div class="checkbox">
                            <input type="checkbox" name="remember" id="remember">
                            <label for="remember">
                                <?php echo _l('clients_login_remember'); ?>
                            </label>
                        </div>
                    </div>
                    <div class="col-sm-6 text-right">
                        <a href="<?php echo site_url('authentication/forgot_password'); ?>">
                            <?php echo _l('customer_forgot_password'); ?>
                        </a>
                    </div>
                </div>
                

                <div class="form-group">
                    <button type="submit" class="btn btn-success btn-block">
                        <?php echo _l('clients_login_login_string'); ?>
                    </button>
                </div>

                <p style="margin-top: 15px; color: #fff;" class="text-center">
                    <?php if (get_option('allow_registration') == 1) { ?>
                        Don't have an account with us, 
                        <a href="<?php echo site_url('authentication/register'); ?>">
                            <?php echo _l('clients_register_string'); ?>
                        </a>
                        now
                    <?php } ?>
                    </p>
                
                <?php hooks()->do_action('clients_login_form_end'); ?>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
<div class="col-md-7 col-lg-8 right-box">
                <div class="carousel fade-carousel slide" data-ride="carousel" data-interval="3000" id="bs-carousel">

                    <!-- Wrapper for slides -->
                    <div class="carousel-inner">
                        <div class="item slides active">
                            <img class="d-block w-100" style=" width: 100%; height: 100%; object-fit: object-fit;" src="<?php echo base_url('assets/images/1.png'); ?>" alt="First slide">
                        </div>
                        <div class="item slides">
                            <img class="d-block w-100" style=" width: 100%; height: 100%; object-fit: cover;" src="<?php echo base_url('assets/images/2.png'); ?>" alt="Second slide">
                        </div>
                        <div class="item slides">
                            <img class="d-block w-100" style=" width: 100%; height: 100%; object-fit: cover;" src="<?php echo base_url('assets/images/3.png'); ?>" alt="Second slide">
                        </div>
                         <div class="item slides">
                            <img class="d-block w-100" style=" width: 100%; height: 100%; object-fit: cover;" src="<?php echo base_url('assets/images/4.png'); ?>" alt="Second slide">
                        </div>
                         <div class="item slides">
                            <img class="d-block w-100" style=" width: 100%; height: 100%; object-fit: cover;" src="<?php echo base_url('assets/images/5.png'); ?>" alt="Second slide">
                        </div>
                    </div> 
                    </div>
</div>

