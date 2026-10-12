<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<meta name="theme-color" content="#0e2a20">
<title>{{ $title }} - FitnessWalkers</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@100..125,500..900&family=Figtree:wght@400..800&display=swap">
<link rel="stylesheet" href="/css/app.css">
</head>
<body class="errbody">
<main class="errpage">
  @include('partials.lanes', ['class' => 'lanes'])
  <div class="errbox">
    <a href="/" aria-label="FitnessWalkers home">@include('partials.logo')</a>
    <div class="errcode">{{ $code }}</div>
    <h1>{{ $title }}</h1>
    <p>{{ $message }}</p>
    <div class="row">
      <a class="btn lime" style="height:48px;border-radius:99px;padding:0 1.5rem" href="/">Go to the home page</a>
      @if (! empty($back))<a class="btn herobtn" style="height:48px;border-radius:99px;padding:0 1.5rem" href="javascript:history.back()">Go back</a>@endif
    </div>
  </div>
</main>
</body></html>
