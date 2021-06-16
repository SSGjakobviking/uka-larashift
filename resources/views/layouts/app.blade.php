<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Styles -->
    <link href="/css/app.css" rel="stylesheet">

    <!-- Scripts -->
    <script>
        window.Laravel = {!! json_encode([
            'csrfToken' => csrf_token(),
        ]) !!};
    </script>
</head>

<body class="<?php echo App\Helpers\UrlHelper::rootRoute(); ?>">
    <div id="app">
        <nav class="navbar navbar-default navbar-static-top">
            <div class="container">
                <div class="navbar-header">

                    <!-- Collapsed Hamburger -->
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#app-navbar-collapse">
                        <span class="sr-only">Toggle Navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>

                    <!-- Branding Image -->
                    <a class="navbar-brand" href="#">
                        {{ config('app.name', 'Laravel') }}-statistik
                        <p>administration</p>
                    </a>
                </div>
                <div class="user-link">
                    @if(auth()->check())
                        <p>Inloggad som <a href="{{ route('users.edit', auth()->user()) }}">{{ auth()->user()->name }}</a></p>
                    @endif
                </div>
                <div class="collapse navbar-collapse" id="app-navbar-collapse">

                    <!-- Right Side Of Navbar -->
                    <ul class="link-list pull-right">
                        <!-- Authentication Links -->

                        @if (Auth::guest())
                            <li><a href="{{ route('login') }}">Logga in</a></li>
                        @else
                            @if (! auth()->user()->hasRole('uppgiftslamnare'))
                        
                            <li>                    
                            
                                <a href="/indicator" style="{{ str_contains(Route::currentRouteName(), "dataset") || str_contains(Route::currentRouteName(), "indicator") ?  'text-decoration:underline;' : '' }}">
                                    Indikatorer
                                </a>
                            </li>
                            <li>
                                <a href="/users" style="{{ str_contains(Route::currentRouteName(), "users") ?  'text-decoration:underline;' : '' }}">
                                    Användare
                                </a>
                            </li>
                            @endif
                            <li>
                                <a href="{{ route('logout') }}"
                                    onclick="event.preventDefault();
                                             document.getElementById('logout-form').submit();">
                                    Logga ut
                                </a>

                                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                    {{ csrf_field() }}
                                </form>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </nav>
        
        <div class="container">
            @yield('content')  
        </div>
    </div>

    <script type="text/javascript">
        var config = {
            baseUrl: '{{ url('/') }}/'
        };
    </script>

    @if(route('dataset.index'))
        <?php 
            $tagsWithDataset = App\Tag::has('datasets')->get()->map(function($tag) {

                return [
                    'id' => $tag->id,
                    'text' => $tag->name,
                ];
            });
        ?>

        <script type="text/javascript">
            var tags = JSON.parse('{!! $tagsWithDataset !!}')
        </script>
    @endif

    <!-- Scripts -->
    <script src="/js/app.js"></script>
           


            <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.tablesorter/2.31.3/js/jquery.tablesorter.min.js"></script>
            <script>
            $(document).ready(function(){
                $(".tablesorter").tablesorter();

                var hash = location.hash.replace(/^#/, '');
                  
                if (hash) {
                    
                    $('.nav-tabs a[href="#' + hash + '"]').tab('show');
                } 

                
                $('.nav-tabs a').on('shown.bs.tab', function (e) {
                    window.location.hash = e.target.hash;
                })
            })
                
            </script>
            <style>
            .tablesorter th {
                background-image: url(data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMiIgaGVpZ2h0PSIxNiIgdmlld0JveD0iMCAwIDE0IDIwIj48cGF0aCBmaWxsPSIjZmZmIiBkPSJNMTQgMTNsLTIuNS0yLjVMNyAxNWwtNC41LTQuNUwwIDEzbDcgN3pNMTQgNy41TDExLjUgMTAgNyA1LjUgMi41IDEwIDAgNy41bDctN3oiLz48L3N2Zz4=);
                background-position: right 5px center;
    background-repeat: no-repeat;
    cursor: pointer;
    white-space: normal;
            }
            .tablesorter thead {
                background-color: #3097D1;
                color: #fff;
            }
            </style>

    @if(config('app.env') == 'local')
        <script src="http://localhost:35729/livereload.js"></script>
    @endif

</body>
</html>
