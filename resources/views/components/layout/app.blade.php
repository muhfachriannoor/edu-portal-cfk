<!DOCTYPE html>
<html lang="en" x-data="{ sidebarOpen: true, profileOpen: false, darkMode: false }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="msapplication-TileImage" content="{{ asset(config('app.logo_path'))}}">
    <meta name="csrf-token" content="{{ csrf_token() }}"> {{-- CSRF token for JS requests --}}

    <title>{{ $header ?? '' }} | {{ config('app.name') }}</title>
    
    <link href="{{ asset('vendors/fontawesome_6.5.0/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
    <link rel="icon" href="{{ asset('assets/img/favicons/logo.ico') }}">
    
    <!-- ===============================================-->
    <!--    Favicons-->
    <!-- ===============================================-->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset(config('app.logo_path'))}}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset(config('app.logo_path')) }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset(config('app.logo_path')) }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset(config('app.logo_path')) }}">
    <link rel="manifest" href="{{ asset('assets/img/favicons/manifest.json') }}">

    <link href="{{ asset('vendors/datatables.net/jquery.dataTables.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendors/daterangepicker/daterangepicker.css') }}" rel="stylesheet">
    <link href="{{ asset('vendors/dropify-master/dist/css/dropify.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendors/flatpickr/dist/flatpickr.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendors/toastr/toastr.css') }}" rel="stylesheet">
    <link href="{{ asset('vendors/perfect-scrollbar/css/perfect-scrollbar.css') }}" rel="stylesheet">
    <link href="{{ asset('vendors/json-viewer/jquery.json-viewer.css') }}" rel="stylesheet">
    <link href="{{ asset('vendors/select2/select2.min.css') }}">
    <link href="{{ asset('assets/css/custom.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/datatable.css') }}" rel="stylesheet">
    <link href="{{ asset('vendors/quill/dist/quill.snow.css') }}" rel="stylesheet">

    <script src="{{ asset('vendors/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendors/alpine@3.14.9/alpine.min.js') }}" defer></script>
    <script src="{{ asset('vendors/tailwind/tailwind.js') }}"></script>
    <script src="{{ asset('vendors/jquery-mask-plugin/jquery.mask.min.js') }}"></script>
    <script src="{{ asset('vendors/moment/min/moment.min.js') }}"></script>
    <script src="{{ asset('vendors/daterangepicker/daterangepicker.js') }}"></script>
    <script src="{{ asset('vendors/dropify-master/dist/js/dropify.min.js') }}"></script>
    <script src="{{ asset('vendors/flatpickr/dist/flatpickr.min.js') }}"></script>
    <script src="{{ asset('vendors/quill/dist/quill.js') }}"></script>
    <script src="{{ asset('vendors/tinymce/tinymce.min.js') }}"></script>
    <script src="{{ asset('vendors/choices/choices.min.js') }}?v=0.0.1"></script>
    <script src="{{ asset('vendors/toastr/toastr.min.js') }}"></script>
    <script src="{{ asset('vendors/perfect-scrollbar/dist/perfect-scrollbar.min.js') }}"></script>
    <script src="{{ asset('vendors/json-viewer/jquery.json-viewer.js') }}"></script>
    <script src="{{ asset('vendors/socket.io-4.7.5/socket.io.min.js') }}"></script>
    <script src="{{ asset('assets/scripts/sweetalert2@11.js') }}"></script>
    <script src="{{ asset('assets/scripts/delete-item.js') }}"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#1e3a8a',
                    }
                }
            }
        }

        $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
      });
    </script>
    <script src="{{ asset('assets/scripts/initialize-doc-ready.js') }}"></script>
    
    <script>
        
    </script>
</head>
<body 
  class="h-screen transition-colors duration-300 bg-[#eceff1] dark:bg-[#1e1e1e] dark:text-white"
  x-data="{ 
    sidebarOpen: window.innerWidth >= 1024,
    profileOpen: false,
    darkMode: false 
  }" 
  @resize.window="sidebarOpen = window.innerWidth >= 1024"
>
  <x-layout.sidebar>
  </x-layout>

  <!-- Main -->
  <div
    :class="sidebarOpen ? 'lg:ml-64' : 'ml-0'"
    class="flex flex-col h-full transition-all duration-100"
    x-cloak
  >

    <x-layout.navbar>
    </x-layout.navbar>

    <!-- Content -->
    <main class="p-6 flex-1 bg-[#F8FAFC]">
      {{ $slot }}
    </main>

    <footer class="text-xs text-gray-400 dark:text-gray-500 text-right px-6 pb-2 bg-[#F8FAFC]">
      {{ date('Y') }} © <a href="{{ env('APP_URL') }}" class="underline">{{ config('app.name') }}</a> <br />
      v1.0.0
    </footer>

  </div>
  @stack('styles')

  <style>
    [x-cloak] {
      display: none !important;
    }
  </style>
  
  <script>
    @if (session('success'))
        toastr.success("{{ session('success') }}");
    @endif

    @if (session('error'))
        toastr.error("{{ session('error') }}");
    @endif

    @if (session('warning'))
        toastr.warning("{{ session('warning') }}");
    @endif

    @if (session('info'))
        toastr.info("{{ session('info') }}");
    @endif  
  </script>

  @stack('scripts')
</body>
</html>
