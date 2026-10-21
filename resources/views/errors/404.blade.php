<!doctype html>
<html
  lang="en"
  class=" layout-wide  customizer-hide"
  dir="ltr"
  data-skin="default"
  data-assets-path="{{asset('/')}}software/"
  data-template="vertical-menu-template-semi-dark"
  data-bs-theme="light">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <meta name="robots" content="noindex" />
    <title>Page not found</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{asset('/')}}software/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="{{asset('/')}}software/vendor/fonts/iconify-icons.css" />



    <link rel="stylesheet" href="{{asset('/')}}software/vendor/css/core.css" />
    <link rel="stylesheet" href="{{asset('/')}}software/css/demo.css" />
    <link rel="stylesheet" href="{{asset('/')}}software/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="{{asset('/')}}software/vendor/css/pages/page-misc.css" />

    <script src="{{asset('/')}}software/vendor/js/helpers.js"></script>
    <script src="{{asset('/')}}software/vendor/js/template-customizer.js"></script>
    <script src="{{asset('/')}}software/js/config.js"></script>
  </head>

  <body>
    <!-- Error -->
    <div class="container-xxl container-p-y">
        <div class="misc-wrapper">
        <h1 class="mb-2 mx-2" style="line-height: 6rem;font-size: 6rem;">404</h1>
        <h4 class="mb-2 mx-2">Page Not Found️ ⚠️</h4>
        <p class="mb-6 mx-2">we couldn't find the page you are looking for</p>
        <a href="{{ route('software.dashboard') }}" class="btn btn-primary mb-10">Back to home</a>
        <div class="mt-4">
            <img src="{{asset('/')}}software/img/illustrations/page-misc-error.png" alt="page-misc-error-light" width="225" class="img-fluid" />
        </div>
        </div>
    </div>
    <div class="container-fluid misc-bg-wrapper">
        <img src="{{asset('/')}}software/img/illustrations/bg-shape-image-light.png" height="355" alt="page-misc-error" data-app-light-img="illustrations/bg-shape-image-light.png" data-app-dark-img="illustrations/bg-shape-image-dark.png" />
    </div>
    <!-- /Error -->

    <!-- Core JS -->
    <script src="{{asset('/')}}software/vendor/libs/jquery/jquery.js"></script>
    <script src="{{asset('/')}}software/vendor/libs/popper/popper.js"></script>
    <script src="{{asset('/')}}software/vendor/js/bootstrap.js"></script>



    <script src="{{asset('/')}}software/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="{{asset('/')}}software/vendor/libs/hammer/hammer.js"></script>
    <script src="{{asset('/')}}software/vendor/libs/i18n/i18n.js"></script>
    <script src="{{asset('/')}}software/vendor/js/menu.js"></script>
    <script src="{{asset('/')}}software/js/main.js"></script>
  </body>
</html>
