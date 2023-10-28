<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    
</head>
<body>
<?php
$subFolder = $_GET['path'] ?? '';
$rootPath = __DIR__ . "/explorer_root";

$isDeleteMode = isset($_GET['del']) && $_GET['del'] === 'true';
$isRenameMode = !empty($_POST['name']);
$isCreateMode = !empty($_POST['create-dir']);

$currentPath = $rootPath;

if($subFolder !== '') {
    $currentPath = prepareFilepath($subFolder);
}

if ($isDeleteMode && is_dir($currentPath)) {
    echo "usuwanie folderu";
    removeDirectory($currentPath);
    header('Location: /explor3r/?path=' . prepareFilepath($_GET['path'], -1));
    return;
}

if ($isDeleteMode) {
    unlink($currentPath);
    header('Location: /explor3r/?path=' . prepareFilepath($_GET['path'], -1));
    return;
}

if ($isRenameMode) {
    rename($currentPath, prepareNewFileName($currentPath, $_POST['name']));
    header('Location: /explor3r/?path=' . prepareFilepath($_GET['path'], -1));
    return;
}

if ($isCreateMode) {
    $folderName = $_POST['create-dir'];
    if (!file_exists("$currentPath/$folderName")) {
    mkdir("$currentPath/$folderName");
    } else {
        echo "Plik lub folder istnieje.";
    }
}


if (!file_exists($currentPath)) {
    exit("ERROR 404. Ta ściezka nie istnieje :(.");
}

if (!is_dir($currentPath)) {
    exit('Ten program nie otwiera plikow!');
}


if(is_dir($currentPath) && file_exists($currentPath)) {
    $items = [];
    foreach (scandir($currentPath) as $item) {
        if ($item === '.') {
            continue;
        }

        if ($item === '..' && isRootDir($currentPath)) {
            continue;
        }

        $itemPath = ($currentPath . '/' . $item);
        // separate the root path from the path
        $itemPathExploded = explode('explorer_root/', $itemPath);
        $items[] = [
            'name' => $item,
            'size' => filesize($itemPath),
            'type' => is_dir($itemPath) ? 'folder' : 'file',
            'path' => '/explor3r/?path=' . $itemPathExploded[1],
        ];
    }
}






if(isset($_POST["file-upload-button"])) {
    $target_file = $currentPath . '/' . basename($_FILES["file-upload"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));


    if (file_exists($target_file)) {
        echo "Sorry, file already exists.";
        $uploadOk = 0;
    }
    
 
    if(!checkExtension($target_file)) {
        echo "";
        $uploadOk = 0;
    }
    
   
    if ($uploadOk == 0) {
        echo "Sorry, your file was not uploaded.";

    } else {
        if (move_uploaded_file($_FILES["file-upload"]["tmp_name"], $target_file)) {
        echo "The file ". htmlspecialchars( basename( $_FILES["file-upload"]["name"])). " has been uploaded.";
        } else {
        echo "Sorry, there was an error uploading your file.";
        }
    }
}

function prepareFilepath(string $subFolderPath, int $level = 0): string
{
    global $rootPath;
    $preparedPath = str_replace('//', '/', urldecode(trim($subFolderPath)));
    $parts = explode('/', $preparedPath);

    if (end($parts) === '..') {
        $parts = array_splice($parts, 0, -2);
    }

    $result = $rootPath . '/' . implode('/', $parts);

    if ($level !== 0) {
        $parts = array_splice($parts, 0, $level);
        $result = implode('/', $parts);
    }

    return $result === '' ? '/' : $result;
}

function removeDirectory(string $path)
{
    $directoryContent = new DirectoryIterator($path);
    foreach($directoryContent as $file) 
    {
        if($file->isFile())
        {
            unlink($file->getRealPath());
        } else if(!$file->isDot() && $file->isDir())
        {
            removeDirectory("$path/$file");
        }
    }
    return rmdir($path);
}

function prepareNewFileName(string $path, string $newName): string
{
    $parts = explode('/', $path);
    if(!is_dir($path)) {
        $fileType = explode(".", $path);
        $parts[sizeof($parts) - 1] = $newName . "." . $fileType[sizeof($fileType) - 1];
    } else {
    $parts[sizeof($parts) - 1] = $newName;
    }
    return implode('/', $parts);
}

function isRootDir(string $currentPath): bool
{
    global $rootPath;
    $currentDirWithoutRoot = str_replace($rootPath, '', $currentPath);
    return empty($currentDirWithoutRoot) || $currentDirWithoutRoot === '/';
}

function checkExtension(string $file) : bool 
{
    $imageFileType = strtolower(pathinfo($file,PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'png', 'gif', 'pdf', 'zip', 'rar', 'doc', 'docx', 'otd'];
    return in_array($imageFileType, $allowedExtensions);
}

function getFolderSize(string $filePath) : int 
{
    $folderSize = 0;
    $directoryContent = new DirectoryIterator($filePath);
    foreach($directoryContent as $file) 
    {
        if($file->isFile())
        {
           $folderSize += filesize($file);
        } else if($file->isDir())
        {
            getFolderSize("$filePath/$file");
        }
    }
    return $folderSize;
}

?>

<div class="table">
    <?php foreach ($items as $index => $item) { ?>
        <?php if ($item['type'] === 'folder') { ?>
            <a class="filename" href="<?= $item['path'] ?>" title="<?= $item['name'] ?>"><?= $item['name'] ?></a> <?php if($item['name'] === '..') { ?> <br> <?php } ?>
        <?php } else { ?>
            <span class="filename" title="<?= $item['name'] ?>"><?= $item['name'] ?></span>
        <?php } ?>
        <?php if($item['name'] !== '..') { ?>
        <span class="size"><?= $item['size'] ?> B</span>
        <span class="type"><?= $item['type'] ?></span>
        <a href="<?= $item['path'] ?>&del=true" class="remove">Usuń</a>
        <form enctype="multipart/form-data" action="<?= $item['path'] ?>" method="post" class="rename-form">
            <label for="rename-<?= $index ?>">Nowa nazwa: </label><input id="rename-<?= $index ?>" name="name">
            <button type="submit">Zmień nazwę</button>
        </form>
        <?php } ?>
    <?php } ?>
    <form action="<?= $_SERVER["REQUEST_URI"] ?>" method="post" class="create-dir-form">
        <input id="create-dir" name="create-dir">
        <button type="submit">Dodaj folder</button>
    </form>
    <form enctype="multipart/form-data" action="<?= $_SERVER["REQUEST_URI"] ?>" method="post" class="upload-form">
        <input id="file-upload" type="file" name="file-upload">
        <button type="submit" name="file-upload-button">Dodaj</button>
    </form>
</div>
</body>
</html>