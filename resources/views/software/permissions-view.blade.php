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
    <meta name="currentGuard" value="{{ (isset($currentGuard) && !empty($currentGuard)) ? $currentGuard : '' }}" />
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
    <script>let dashboard_url = "{{ route('software.dashboard') }}";</script>
  </head>

  <body>
    <!-- Error -->
    <div class="container-xxl container-p-y">
        <div class="misc-wrapper">
        <h1 class="mb-2 mx-2" style="line-height: 3rem;font-size: 4rem;">location Permission allow</h1>
        {{-- <a href="javascript:;" id="allow_permission_btn" class="btn btn-primary mt-4">Allow Permission</a> --}}
        <div class="mt-4">
            <img src="http://127.0.0.1:8000/software/img/illustrations/page-misc-error.png" alt="page-misc-error-light" width="225" class="img-fluid">
        </div>
        </div>
    </div>
    <div class="container-fluid misc-bg-wrapper">
        <img src="{{asset('/')}}software/img/illustrations/bg-shape-image-light.png" height="355" alt="page-misc-error" data-app-light-img="illustrations/bg-shape-image-light.png" data-app-dark-img="illustrations/bg-shape-image-dark.png" />
    </div>

    <div id="navigator_location_info" data-navigator-location-info=""></div>
    <div id="navigator_browser_info" data-navigator-browser-info=""></div>
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
    <script src="{{asset('/')}}software/custom/custom.js"></script>
    <script>
    // $(document).on('click', '#allow_permission_btn', function() {
    //     getGeolocationcall();
    // });

    </script>
  </body>
</html>
