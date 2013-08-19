<?php

$analytics = 'UA-36046051-5';
$messages = array( 
	1 => "The uploaded file exceeds the system maximum", 
	2 => "The uploaded file exceeds the form maximum",
	3 => "The uploaded file was only partially uploaded", 
	4 => "No file was uploaded", 
	6 => "Missing a temporary folder",
	7 => "Failed to write file to disk",
	8 => "Something stopped the file upload"
);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	try {
	
		if ($_FILES['image']['error'] != 0 && $_FILES['image']['error'] != 4) {
			throw new Exception($messages[$_FILES['image']['error']]);
		}

		$left = (int) $_POST['left'];
		
		if ($left != $_POST['left']) {
			throw new Exception('Left end cap is not a number.');
		}

		$top = (int) $_POST['top'];
		
		if ($top != $_POST['top']) {
			throw new Exception('Top end cap is not a number.');
		}

        $percentage = (int) $_POST['percentage'];
        
        if ($percentage != $_POST['percentage']|| $percentage < 1 || $percentage > 100) {
            throw new Exception('Percentage is not valid.');
        }

		if (preg_match('/^(.+)(@2x)?(\.[^\.]+$)/U', $_FILES['image']['name'], $matches)) {
			
			if ($matches[2]) {
				$top = $top * 2;
				$left = $left * 2;
			}

			$filename = $matches[1] . '.9' . $matches[3];

		} else {
			throw new Exception('Unsupported filename');
		}

		$image = new Imagick();
		$image->readImage($_FILES['image']['tmp_name']);

        if ($percentage !== 100) {
            $image->resizeImage(($image->getImageWidth() / 100) * $percentage, ($image->getImageHeight() / 100) * $percentage);
        }

		$pixel = new Imagick();
		$pixel->newImage(1, 1, new ImagickPixel('black'));
			
		$patched = new Imagick();
		$patched->newImage($image->getImageWidth() + 2, $image->getImageHeight() + 2, new ImagickPixel('none'));
		$patched->setImageFormat('png');
		$patched->compositeImage($image, imagick::COMPOSITE_OVER, 1, 1);
		$patched->compositeImage($pixel, imagick::COMPOSITE_OVER, 0, $top + 1);
		$patched->compositeImage($pixel, imagick::COMPOSITE_OVER, $left + 1, 0);
		
		header("Content-Type: image/png");
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		echo $patched;
	
	} catch (Exception $e) {
		$error = $e->getMessage();
	}
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>9patch - Generate a 9-patch image from an iOS image and its left and top end caps</title>
    <meta name="description" content="Generate a 9-patch image from an iOS image and its left and top end caps">
    <meta name="author" content="Fokke Zandbergen">
    <link href="jbootstrap/css/bootstrap.css" rel="stylesheet">
    <style type="text/css">
    
      body {
        padding-top: 20px;
        padding-bottom: 40px;
      }

      .container-narrow {
        margin: 0 auto;
        max-width: 700px;
      }
      .container-narrow > hr {
        margin: 30px 0;
      }

      .jumbotron {
        margin: 60px 0;
        text-align: center;
      }
      
      .form .row-fluid {
      	margin: 1em 0;
      }
      
      .form h4 {
      	margin-top: 0;
      }
      
      .about {
        margin: 60px 0;
      }
      
      .about p + h4 {
        margin-top: 28px;
      }
      
    </style>
	<script src="http://code.jquery.com/jquery.js"></script>
    <script src="jbootstrap/js/bootstrap.min.js"></script>
  </head>

  <body>
  
  	<? if ($analytics): ?>
	  <script>
		(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

		ga('create', '<?= $analytics ?>');
		ga('send', 'pageview');
  
		$(document).ready(function () {
		  $('#generate').click(function (e) {
			  ga('send', 'event', 'button', 'click', 'generate');
		  });
		});

	  </script>
	<? endif ?>
	
    <div class="container-narrow">

      <div class="masthead">
        <ul class="nav nav-pills pull-right">
          <li><a href="#about">About</a></li>
          <li><a href="https://github.com/FokkeZB/9patch" target="_blank">Fork on GitHub</a></li>
        </ul>
        <h1><span class="muted">9</span>patch</h1>
      </div>

      <hr>

      <div class="jumbotron">
        <h2>Generate a 9-patch image from an iOS image</h2>
        <p class="lead">Select the image and enter its left and top end caps.</p>
      </div>
      
      <? if ($error): ?>
      	<div class="alert alert-error"><?= $error ?></div>
      <? endif ?>
      
      <div class="form">
        <form action="./" method="post" enctype="multipart/form-data">
        <input type="hidden" name="MAX_FILE_SIZE" value="<?= 10 * 1024 * 1204 ?>" /> 
			<div class="row-fluid">
				<div class="span3"><h4>Image</h4></div>
				<div class="span3">
					<div class="fileupload fileupload-new" data-provides="fileupload">
					  <div class="fileupload-new thumbnail" style="width: 100px; height: 100px;"><img src="http://dummyimage.com/100x100/eeeeee/333333.png&text=%201024x1024%20" /></div>
					  <div class="fileupload-preview fileupload-exists thumbnail" style="width: 100px; height: 100px;"></div>
					  <div>
						<span class="btn btn-file"><span class="fileupload-new">Select</span><span class="fileupload-exists">Replace</span><input type="file" name="image" accept="image/png" /></span>
						<a href="#" class="btn fileupload-exists" data-dismiss="fileupload">Remove</a>
					  </div>
					</div>
				</div>
				<div class="span6">If the filename contains <code>@2x</code> then the left and top end caps are multiplied by 2. The file you download will have <code>@2x</code> removed and a <code>.9.png</code> extension.</div>
			</div>
			<div class="row-fluid">
				<div class="span3"><h4>Left cap</h4></div>
				<div class="span3">
					<input type="text" name="left" class="input-mini" placeholder="0" />
				</div>
				<div class="span6">Only the next 1px wide column will be resized.</div>
			</div>
			<div class="row-fluid">
				<div class="span3"><h4>Top cap</h4></div>
				<div class="span3">
					<input type="text" name="top" class="input-mini" placeholder="0" />
				</div>
				<div class="span6">Only the next 1px heigh row will be resized.</div>
			</div>
            <div class="row-fluid">
                <div class="span3"><h4>Percentage</h4></div>
                <div class="span3">
                    <input type="text" name="percentage" class="input-mini" placeholder="100%" />
                </div>
                <div class="span6">Downsize the image while securing a 1px 9-patch border.</div>
            </div>
			<div class="row-fluid">
				 <div class="offset3 span4">
				 	<input type="submit" class="btn btn-large btn-success" value="Generate" id="generate" />
				 </div>
			</div>
        </form>
      </div>
        
      <hr>

	  <a name="about"></a>
      <div class="row-fluid about">
      
        <div class="span6">
        
        	<h4>Why</h4>
        	<p>This little project was born out of lazyness.</p>

        </div>

        <div class="span6">
        
        	<h4>Caps</h4>
        	<p>Information on how caps on iOS work can be found in the <a href="http://docs.appcelerator.com/titanium/latest/#!/api/Titanium.UI.View" target="_blank">Ti.UI.View documentation</a>.</p>
        	
        	<h4>9-patch</h4>
        	<p>Information on 9-patch images on Android can be found in the <a href="http://docs.appcelerator.com/titanium/latest/#!/api/Titanium.UI.ImageView" target="_blank">Ti.UI.ImageView documentation</a>.</p>

        </div>
      </div>

      <hr>

      <div class="footer">
        <p>&copy; <a href="http://www.fokkezb.nl" target="_blank">Fokke Zandbergen</a> 2013 - <a href="https://github.com/FokkeZB/9patch">License</a></p>
      </div>

    </div>

  </body>
</html>

