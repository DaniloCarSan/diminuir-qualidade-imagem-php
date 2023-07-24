<?php 

class ImageManager
{
    private $max_width = 1280;
    private $max_height = 960;

    private $fileSize;
    private $fileHeight;
    private $fileWidth;
    private $fileExtension;
    private $filePath;
    private $fileMime;
    private $fileType;

    private $quality  = 0;

    private $extensions = [
        'png',
        'x-png',
        'jpg',
        'jpeg',
        'pjpeg',
        'gif'
    ];

    private $qualityPerSize= [
        30 =>1,
        40 =>0.5,
        60 =>0.3,
        80 => 0
    ];

    public function __construct($filePath)
    {
        if( ! file_exists($filePath) )
        {
            throw new \Exception('File '.$filePath.' not found');
        }

        $this->setFilePath($filePath);
        $this->setFileExtension();
        
        if( ! in_array($this->getFileExtension(),$this->extensions) )
        {
            throw new \Exception('Extension '.$this->fileExtension.' not suportable');
        }
        
        $this->setFileMime();
        $this->setFileInfo();
        $this->setQuality();

    }

    public function setMaxWidth ($max_width)
    {
        $this->max_width = $max_width;

        return $this;
    }

    public function setMaxHeight($max_height)
    {
        $this->max_height = $max_height;

        return $this;
    }

    public function setMaxWH($max_width,$max_height)
    {
        $this->setMaxWidth($max_width);
        $this->setMaxHeight($max_height);
        return $this;
    }

    private function setQuality()
    {
        foreach($this->qualityPerSize as $quality => $size)
        {
            if($this->fileSize >= $size )
            {
                $this->quality = $quality;
                break;
            }
        }
    }

    public function getFileSize()
    {
        return $this->fileSize;
    }

    private function setFilePath($filePath)
    {
        $this->filePath = $filePath;
    }

    public function getFilePath()
    {
        return $this->filePath;
    }

    private function setFileMime()
    {
        $this->fileMime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $this->getFilePath());
    }

    private function getFileMime()
    {
        return $this->fileMime;
    }

    private function setFileExtension()
    {
        $this->fileExtension = strtolower(pathinfo($this->getFilePath(), PATHINFO_EXTENSION));
    }

    public function getFileExtension(){
        return $this->fileExtension;
    }

    private function getResource()
    {
        switch ($this->getFileMime())
        {
            case 'image/jpg':
            case 'image/jpeg':
            case 'image/pjpeg':
                $resource = imagecreatefromjpeg($this->getFilePath());
            break;    
            case 'gif':
                $resource = imagecreatefromgif($this->getFilePath());
            break;
            case 'image/png':
            case 'image/x-png':
                $resource = imagecreatefrompng($this->getFilePath());
            break;
        }
        
        if (empty($resource)) {
            throw new \Exception(
                "Unable to decode image from file ({$this->getFilePath()})."
            );
        }
        return $resource;
    }

    private function setFileInfo()
    {
        list($width, $height, $image_type) = getimagesize($this->getFilePath());
        $this->fileHeight = $height;
        $this->fileWidth = $width;
        $this->fileType = $image_type;
        $this->fileSize = filesize($this->getFilePath())/1024/1024;
    }

    private  function getNewImage()
    {
        $x_ratio = $this->max_width / $this->fileWidth;
        $y_ratio = $this->max_height / $this->fileHeight;
        
        if( ($this->fileWidth <= $this->max_width) && ($this->fileHeight <= $this->max_height) )
        {
            $tn_width  = $this->fileWidth;
            $tn_height = $this->fileHeight;
        }
        elseif (($x_ratio * $this->fileHeight) < $this->max_height)
        {
            $tn_height = ceil($x_ratio * $this->fileHeight);
            $tn_width = $this->max_width;
        }
        else
        {
            $tn_width = ceil($y_ratio * $this->fileWidth);
            $tn_height = $this->max_height;
        }   
        
        $tmp = imagecreatetruecolor($tn_width,$tn_height);

        /* Check if this image is PNG or GIF, then set if Transparent*/
        if(($this->fileType == 1) OR ($this->fileType==3))
        {
            imagealphablending($tmp, false);
            
            imagesavealpha($tmp,true);
            
            $transparent = imagecolorallocatealpha($tmp, 255, 255, 255, 127);
            
            imagefilledrectangle($tmp, 0, 0, $tn_width, $tn_height, $transparent);
        }

		imagecopyresampled($tmp,$this->getResource(),0,0,0,0,$tn_width, $tn_height, $this->fileWidth, $this->fileHeight);

        return $tmp;
    }

    public function save($savePath=null,$quality=null)
    {
        if(! is_null($savePath))
        {
            $this->setFilePath($savePath);
        }

        if(! is_null($quality))
        {
            $this->quality = $quality ;
        }

        return imagejpeg($this->getNewImage(),$this->getFilePath(),$this->quality);
    }

}


$pathOld = "./converter/";
$pathNew = "./convertido/";
$directoryOld = dir($pathOld);

while($fileName = $directoryOld->read())
{
    $old = $pathOld.$fileName;
    $new = $pathNew.$fileName;

    if( ! in_array($fileName,['.','..']) )
    {
        if(file_exists($old))
        {
            echo 'comprimino imagem (<b>'.$old.'</b>)...<br>';
            (new ImageManager($old))->save($old);
            echo 'movendo imagem de (<b>'.$old.'</b>) para (<b>'.$new.'</b>)<br>';
            rename($old,$new);
            echo '<hr>';
        }

    }

}
$directoryOld -> close();


