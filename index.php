<?php
session_start();

// Prüfe, ob der Benutzer authentifiziert ist, andernfalls leite ihn zur Anmeldung weiter
if (!isset($_SESSION['access_token'])) {
    header('Location: /login.php');
    exit();
}

// Funktionen für den Datei-Upload und Spam-Überprüfung
function getUploads() {
    if (!isset($_SESSION['uploads'])) {
        $_SESSION['uploads'] = [];
    }
    return $_SESSION['uploads'];
}

function addUpload() {
    $_SESSION['uploads'][] = time();
}

function clearOldUploads() {
    $oneHourAgo = time() - 3600;
    $_SESSION['uploads'] = array_filter(getUploads(), function ($timestamp) use ($oneHourAgo) {
        return $timestamp > $oneHourAgo;
    });
}

function canUpload() {
    clearOldUploads();
    return count(getUploads()) < 5;
}

function antiSpamUpload() {
    if (isset($_SESSION['last_upload_time'])) {
        $minTimeBetweenUploads = 60;
        $lastUploadTime = $_SESSION['last_upload_time'];
        $currentTime = time();
        $timeSinceLastUpload = $currentTime - $lastUploadTime;
        if ($timeSinceLastUpload < $minTimeBetweenUploads) {
            return true;
        }
    }
    $_SESSION['last_upload_time'] = time();
    return false;
}

function zuVieleUploads() {
    $maxUploadsPerHour = 5;
    $timePeriodInSeconds = 3600;
    $uploads = isset($_SESSION['uploads']) ? $_SESSION['uploads'] : [];
    $uploads = array_filter($uploads, function($timestamp) use ($timePeriodInSeconds) {
        return $timestamp > (time() - $timePeriodInSeconds);
    });
    $uploads[] = time();
    $_SESSION['uploads'] = $uploads;
    return count($uploads) > $maxUploadsPerHour;
}

// Verarbeitung des Dateiuploads beim POST-Request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Überprüfung auf Spam
    if (antiSpamUpload()) {
        echo "Please wait for a moment before uploading again.";
    } elseif (zuVieleUploads()) {
        echo "You have uploaded too many files within a short period of time. Please try again later.";
    } elseif (!canUpload()) {
        echo "You have reached the limit of 5 uploads per hour. Please try again later.";
    } else {
        // Durchführung des Dateiuploads
        $uploadDirectory = 'uploads/';
        $fileName = $_FILES['file']['name'];
        $fileTmpName = $_FILES['file']['tmp_name'];
        $fileType = $_FILES['file']['type'];
        $fileSize = $_FILES['file']['size'];
        $fileError = $_FILES['file']['error'];
        $title = isset($_POST['title']) ? $_POST['title'] : '';
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = array('png', 'jpg', 'jpeg', 'webp', 'gif', 'mpg', 'mpeg', 'mpe', 'mpe');
        if (in_array($fileExt, $allowedExtensions)) {
            if ($fileError === 0) {
                $uploadPath = $uploadDirectory . $fileName;
                if (!file_exists($uploadPath)) {
                    if (move_uploaded_file($fileTmpName, $uploadPath)) {
                        addUpload();
                        echo "Your file is online <3";
                        echo "<br>";
                        echo "URL: <a href='$uploadPath'>$uploadPath</a>";
                    } else {
                        echo "Your file has an error :(.";
                    }
                } else {
                    echo "Your file already exists";
                }
            } else {
                echo "Your file cannot be uploaded";
            }
        } else {
            echo "Invalid file type. Please only upload videos, audios, or images.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="description" content="Upload Image,Videos,Audios for FREE and without a Account">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FAYSi Social</title>
<style>
 .news {
            color: red;
        }

        body {
            font-family: Arial, sans-serif;
            text-align: center;
            background: linear-gradient(to right, #000000, #02746b); /* Neuer Farbverlauf */
            color: #fff;
            position: relative;
        }

        .upload-btn {
            position: fixed;
            top: 10px;
            right: 10px;
        }

        .popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            padding: 20px;
            border: 2px solid #ccc;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            z-index: 9999;
            border-color: blue;
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            border-color: blue;
        }

        .video-container {
            margin-top: 120px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }

        .video-container a {
            display: inline-block;
            margin: 10px;
        }

        .video-container img {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            transition: transform 0.3s;
            border: 5px solid white; /* Add border around the images */
        }

        .video-container img:hover {
            transform: scale(1.1);
        }

        .video-container video {
            width: 200px;
            height: 200px;
            border-radius: 10px;
            margin: 10px;
        }

        .video-container audio {
            margin: 10px;
        }

        form {
            margin-bottom: 20px;
        }

        form select,
        form input[type="text"],
        form button {
            border: 2px solid #007bff;
            border-radius: 5px;
            padding: 8px 12px;
            margin-right: 10px;
        }

        form select {
            background-color: #f8f9fa;
        }

        form select option[value=""] {
            background-color: #007bff;
            color: #fff;
        }

        form button {
            background-color: #007bff;
            color: #fff;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        form button:hover {
            background-color: #0056b3;
        }

        input[type="file"],
        input[type="text"] {
            margin-bottom: 10px;
            padding: 8px 12px;
            border: 2px solid #007bff;
            border-radius: 5px;
            display: inline-block;
            background-color: white;
        }

        .dark-mode input[type="file"],
        .dark-mode input[type="text"],
        .dark-mode form button {
            border-color: #fff;
            background-color: #333;
            color: #fff;
        }

        .dark-mode form select {
            background-color: #666;
            color: #fff;
        }

        .dark-mode form button:hover {
            background-color: #bbb;
        }

        .note {
            color: green;
            border-color: black;
        }

        .support {
            border-radius: 15px;
            background-color: white;
            border-color: blue;
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1;
        }

        .update {
            border-radius: 15px;
            background-color: white;
            border-color: blue;
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1;
        }

        button {
            border-radius: 20px;
            border: 2px solid blue;
            padding: 10px 20px;
            font-size: 16px;
            font-weight: bold;
            color: white;
            background-color: #333;
            cursor: pointer;
            z-index: 2;
            border-color: white;
        }

        button:hover {
            background-color: #555;
            color: white;
        }

        a {
            color: white;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
        }

        a:hover {
            color: blue;
            transition: all 0.3s ease;
        }
        .info{
        color: red;
        }
</style>
</head>
<body>

  <form action="report.php">
    <button class="support">Report</button><br>
  </form><br>
  <form action="update.html">
    <button class="update">Changelog</button>
  </form>
 <hr>
  <a href="index.php">
    <h1>FAYSi Social</h1>
  </a>
  <?php
  $newsFile = 'news.txt';
  if (file_exists($newsFile)) {
      $newsContent = file_get_contents($newsFile);
      if (!empty($newsContent)) {
          echo "<div class='news'>$newsContent</div>";
      }
  }

  $backgroundColorFile = 'background_color.txt';
  $backgroundColor = 'background: #000000;';
  if (file_exists($backgroundColorFile)) {
      $backgroundColorContent = file_get_contents($backgroundColorFile);
      if (!empty($backgroundColorContent)) {
          $backgroundColor = "background: $backgroundColorContent;";
      }
  }

  $titleFile = 'title.txt';
  $title = 'FAYSi Social';
  if (file_exists($titleFile)) {
      $titleContent = file_get_contents($titleFile);
      if (!empty($titleContent)) {
          $title = htmlspecialchars($titleContent);
      }
  }
  ?>
  <style>
  body {
      <?php echo $backgroundColor; ?>
  }
  </style>

  <form id="filterForm">
    <select name="category" class="category">
      <option value="">All</option>
      <option value="videos">Videos</option>
      <option value="audios">Audios</option>
      <option value="pictures">Pictures</option>
    </select>
    <input type="text" name="search" placeholder="Search..." class="search">
    <button type="submit">Search/Filter</button>
  </form>
  <div>Click (+) for upload your videos, pictures or audios</div><br><br><br>

  <div class="upload-btn">
    <button id="uploadBtn">+</button>
  </div>
  
  <div class="popup" id="uploadPopup" style="display: none;"> 
    <h2>Upload Files</h2>
    <form action="" method="post" enctype="multipart/form-data">
      <input type="file" name="file" accept="video/*, audio/*, image/*" class="file-upload">
      <input type="text" name="title" placeholder="Title" class="title">
      <button type="submit" class="upload-button">Upload Files</button>
    </form>
  </div>

  <div class="video-container">
    <?php
    $uploadDirectory = 'uploads';
    
    function displayUploadedFiles($directory, $category = '', $search = '') {
        if (is_dir($directory)) {
            $files = scandir($directory);
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    $filePath = $directory . '/' . $file;
                    $fileExt = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (($category === '' || $category === 'all' || ($category === 'videos' && in_array($fileExt, ['mp4', 'mov', 'avi'])) ||
                        ($category === 'audios' && in_array($fileExt, ['mp3', 'wav'])) ||
                        ($category === 'script' && in_array($fileExt, ['html', 'js'])) ||
                        ($category === 'pictures' && in_array($fileExt, ['png', 'jpg', 'jpeg', 'webp','gif']))) &&
                        (empty($search) || strpos(strtolower($file), strtolower($search)) !== false)) {
                            
                            if (in_array($fileExt, ['png', 'jpg', 'jpeg', 'webp', 'gif'])) {
                                echo "<a href='$filePath' target='_blank'><img src='$filePath' alt='$file'></a>";
                            } else {
                              
                                echo "<a href='$filePath' target='_blank'><img src='" . getPreviewImage($fileExt) . "' alt='$file'></a>";
                            }
                    }
                }
            }
        }
    }

    // public image(change your own in /public)
    function getPreviewImage($fileExt) {
        
        $previewImages = [
            'mp4' => 'videos.png',
            'mov' => 'videos.png',
            'avi' => 'videos.png',
            'mp3' => 'audio.png',
            'wav' => 'audio.png',
         
        ];

       
        if (array_key_exists($fileExt, $previewImages)) {
            return 'public/' . $previewImages[$fileExt];
        } else {
           
            return 'public/default.png';
        }
    }

    $category = isset($_GET['category']) ? $_GET['category'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';

    displayUploadedFiles($uploadDirectory, $category, $search);
    ?>
  </div>

<script>
document.getElementById("uploadBtn").addEventListener("click", function() {
  document.getElementById("uploadPopup").style.display = "block";
  document.body.appendChild(document.createElement('div')).className = "overlay";
});

document.body.addEventListener("click", function(e) {
  if (e.target.classList.contains("overlay")) {
    document.getElementById("uploadPopup").style.display = "none";
    document.querySelector(".overlay").remove();
  }
});

document.querySelector('select[name="category"]').addEventListener('change', function() {
  document.getElementById('filterForm').submit();
});

document.addEventListener('DOMContentLoaded', function() {
  document.body.classList.add('dark-mode'); 
});
</script>
</body>
	<footer>
		<hr>
		<a href="tos.html">TO's</a> |
		<a href="impresum.html">Impresum</a> | <a href="logout.php">Logout</a>
	</footer>
</html>
