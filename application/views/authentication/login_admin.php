<!--<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('authentication/includes/head.php'); ?>

<body class="tw-bg-neutral-100 login_admin">

    <div class="tw-max-w-md tw-mx-auto tw-pt-24 authentication-form-wrapper tw-relative tw-z-20">
        <div class="company-logo text-center">
            <?php get_dark_company_logo(); ?>
        </div>

        <h1 class="tw-text-2xl tw-text-neutral-800 text-center tw-font-semibold tw-mb-5">
            <?php echo _l('admin_auth_login_heading'); ?>
        </h1>

        <div class="tw-bg-white tw-mx-2 sm:tw-mx-6 tw-py-6 tw-px-6 sm:tw-px-8 tw-shadow tw-rounded-lg">

            <?php $this->load->view('authentication/includes/alerts'); ?>

            <?php echo form_open($this->uri->uri_string()); ?>

            <?php echo validation_errors('<div class="alert alert-danger text-center">', '</div>'); ?>

            <?php hooks()->do_action('after_admin_login_form_start'); ?>

            <div class="form-group">
                <label for="email" class="control-label">
                    <?php echo _l('admin_auth_login_email'); ?>
                </label>
                <input type="email" id="email" name="email" class="form-control" autofocus="1">
            </div>

            <div class="form-group">
                <label for="password" class="control-label">
                    <?php echo _l('admin_auth_login_password'); ?>
                </label>
                <input type="password" id="password" name="password" class="form-control">
            </div>

            <?php if (show_recaptcha()) { ?>
            <div class="g-recaptcha tw-mb-4" data-sitekey="<?php echo get_option('recaptcha_site_key'); ?>"></div>
            <?php } ?>

            <div class="form-group">
                <div class="checkbox checkbox-inline">
                    <input type="checkbox" value="estimate" id="remember" name="remember">
                    <label for="remember"> <?php echo _l('admin_auth_login_remember_me'); ?></label>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">
                    <?php echo _l('admin_auth_login_button'); ?>
                </button>
            </div>

            <div class="form-group">
                <a href="<?php echo admin_url('authentication/forgot_password'); ?>">
                    <?php echo _l('admin_auth_login_fp'); ?>
                </a>
            </div>

            <?php hooks()->do_action('before_admin_login_form_close'); ?>

            <?php echo form_close(); ?>
        </div>
    </div>

</body>

</html>-->
<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('authentication/includes/head.php'); ?>

<style>
    .login_admin .login-admin-form {
    background: #012947;
    }
    body.login_admin{
        background: none;
    }
    .login_admin .login-admin-form h1 {
    color: #fff !important;
    font-family: 'Poppins';
        margin-bottom: 35px;
        font-size: 20px;
    }
    .login_admin .login-admin-form label.control-label, .login_admin .login-admin-form .checkbox label {
    color: #fff;
    font-weight: 300;
    }
    body.login_admin .login-admin-form .btn.btn-primary.btn-block {
    background: #39c529;
    border-color: #39c529;
    }
    body.login_admin .login-admin-form .btn.btn-primary.btn-block:hover, body.login_admin .login-admin-form .btn.btn-primary.btn-block:focus {
    background: #40a04d;
    border-color: #40a04d;
    }
    body.login_admin a {
    color: #39c529;
    }
    .mtop40 {
        margin-top: 40px;
    }

    .align-items-center{
        align-items: center
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

    @media (min-width: 992px) {
        .row{
            display: flex
        }
        .right-box {
            min-height: 100vh;
        }
        .left-box{padding-right: 30px}
    }
    @media (min-width: 1200px) {
        .left-box{padding-right: 50px}
    }
</style>

<body class="tw-bg-neutral-100 login_admin">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-5 col-lg-4 mtop40 left-box">
                <div class=" authentication-form-wrapper tw-relative tw-z-20">
                    <div class="company-logo text-center">
                        <?php get_dark_company_logo(); ?>
                    </div>

                    <div class="tw-bg-white tw-py-6 tw-px-6 sm:tw-px-8 tw-shadow tw-rounded-lg login-admin-form">
                        <h1 class="tw-font-semibold tw-mt-0 text-center">
                            <?php echo _l('admin_auth_login_heading'); ?>
                        </h1>
                        <div class="d-flex tab-buttons">
                            <a href="<?php echo site_url('admin/authentication'); ?>" class="btn-link active">Company</a>
                            <a href="<?php echo site_url('authentication/login'); ?>" class="btn-link">Vendor</a>
                        </div>
                        <?php $this->load->view('authentication/includes/alerts'); ?>
                        
                        <?php echo form_open($this->uri->uri_string()); ?>

                        <?php echo validation_errors('<div class="alert alert-danger text-center">', '</div>'); ?>

                        <?php hooks()->do_action('after_admin_login_form_start'); ?>

                        <div class="form-group">
                            <label for="email" class="control-label">
                                <?php echo _l('admin_auth_login_email'); ?>
                            </label>
                            <input type="email" id="email" name="email" class="form-control" autofocus="1">
                        </div>

                        <div class="form-group">
                            <label for="password" class="control-label">
                                <?php echo _l('admin_auth_login_password'); ?>
                            </label>
                            <input type="password" id="password" name="password" class="form-control">
                        </div>

                        <?php if (show_recaptcha()) { ?>
                        <div class="g-recaptcha tw-mb-4" data-sitekey="<?php echo get_option('recaptcha_site_key'); ?>"></div>
                        <?php } ?>

                        <div class="row align-items-center form-group">
                            <div class="col-sm-6">
                                <div class="checkbox checkbox-inline">
                                    <input type="checkbox" value="estimate" id="remember" name="remember">
                                    <label for="remember"> <?php echo _l('admin_auth_login_remember_me'); ?></label>
                                </div>
                            </div>
                            <div class="col-sm-6 text-right">
                                <a href="<?php echo admin_url('authentication/forgot_password'); ?>">
                                    <?php echo _l('admin_auth_login_fp'); ?>
                                </a>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-block">
                                <?php echo _l('admin_auth_login_button'); ?>
                            </button>
                        </div>

                        <?php hooks()->do_action('before_admin_login_form_close'); ?>

                        <?php echo form_close(); ?>
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
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>

</body>

</html>