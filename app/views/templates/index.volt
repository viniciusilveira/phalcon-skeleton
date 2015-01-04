<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Phalcon Skeleton</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{ assets.outputCss() }}
</head>
<body>
<a href="#" id="top"></a>
<!--[if lt IE 7]>
<p class="browsehappy">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
<![endif]-->

{% include 'partials/main/header.volt' %}

<div id="messages">{{ flash.output() }}</div>

{{ content() }}

{% include 'partials/main/footer.volt' %}

{{ assets.outputJs() }}
</body>
</html>
