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
                    <ul class="nav navbar-nav navbar-right">
                        <!-- Authentication Links -->

                        @if (Auth::guest())
                            <li><a href="{{ route('login') }}">Logga in</a></li>
                        @else
                            @if (! auth()->user()->hasRole('uppgiftslamnare'))
                            <li>
                                <a href="/dataset">
                                    Dataset
                                </a>
                            </li>
                            
                            <li>
                                <a href="/indicator">
                                    Indikatorer
                                </a>
                            </li>
                            <li>
                                <a href="/users">
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

    @if(config('app.env') == 'local')
        <script src="http://localhost:35729/livereload.js"></script>
    @endif

</body>
</html>
