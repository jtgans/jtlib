<?PHP

define("IMAGETYPE_GIF",  1);
define("IMAGETYPE_JPEG", 2);
define("IMAGETYPE_PNG",  3);
define("IMAGETYPE_SWF",  4);
define("IMAGETYPE_PSD",  5);
define("IMAGETYPE_BMP",  6);
define("IMAGETYPE_TIFF_II", 7);
define("IMAGETYPE_TIFF_MM", 8);
define("IMAGETYPE_JPC",  9);
define("IMAGETYPE_JP2", 10);
define("IMAGETYPE_JPX", 11);
define("IMAGETYPE_SWC", 12);

class Image
{
    var $img;
    var $status;
    var $error;
    var $type;
    
    function Image($file = false)
    {
        $status = false;
        $error = false;
    
        if ($file) {
            $this->loadFromFile($file);
        }
    }

    function loadFromFile($file)
    {
        if (!file_exists($file)) {
            Utils::errorMsg("Image", __LINE__, "Unable to open $file for reading!");
            exit;
        }

        $type = $this->getImageType($file);
    
        switch ($type) {
            //   case IMAGETYPE_GIF:
            //      $this->img = imagecreatefromgif($file);
            //      break;
        
            case IMAGETYPE_JPEG:
                $this->img = imagecreatefromjpeg($file);
                break;
        
            case IMAGETYPE_PNG:
                $this->img = imagecreatefrompng($file);
                break;
        
            default:
                $this->error = true;
                $this->status = "Unknown image format for $file";
                return false;
                break;
        }

        $this->type = $type;
    
        return true;
    }

    function getImageType($file)
    {
        if (!file_exists($file)) return false;
    
        $result = getimagesize($file);
    
        return $result[2];
    }
    
    function isLoadable($file)
    {
        if (!file_exists($file)) return false;
    
        $result = getimagesize($file);
        $result = $result[2];
    
        switch ($result) {
            case IMAGETYPE_JPEG:
            case IMAGETYPE_PNG:
                return true;
                break;
        
            case IMAGETYPE_GIF:
            default:
                return false;
                break;
        }
    }
    
    function getOrientation()
    {
        // Figure out if the image is longer in the X or Y direction
        if (imagesx($this->img) > imagesy($this->img))  $rect = "horizontal";
        if (imagesy($this->img) > imagesx($this->img))  $rect = "vertical";
        if (imagesx($this->img) == imagesx($this->img)) $rect = "square";

        return $rect;
    }
    
    function resample($width, $height)
    {
        if (!$this->img) {
            $this->error = true;
            $this->status = "Attempted to resample without an image!";
            return true;
        }
    
        $tmp_img = imagecreatetruecolor($width, $height);
    
        imagecopyresampled($tmp_img, $this->img, 0, 0, 0, 0, $width, $height, imagesx($this->img), imagesy($this->img));
        imagedestroy($this->img);
    
        $this->img = $tmp_img;
        return true;
    }

    function resamplePercent($percent)
    {
        if (!$this->img) {
            $this->error = true;
            $this->status = "Attempted to resampel without an image!";
            return true;
        }
    
        $width = imagesx($this->img) * ($percent / 100);
        $height = imagesy($this->img) * ($percent / 100);
    
        $this->resample($width, $height);
    }

    function resampleToHeight($height)
    {
        // Calculate the ratio needed to keep the aspect correct
        // What we have is w1, h1, and h2. What we need is w2
        //
        // w1   x
        // -- = --
        // h1   h2
        //
        // So, the formula is (h2 * w1) / h1
    
        $width = floor(($height * imagesx($this->img)) / imagesy($this->img));
    
        $this->resample($width, $height);
    }
    
    function resampleToWidth($width)
    {
        // Same deal as above, except now we're looking for h2 instead of w2
        //
        // h1   x
        // -- = --
        // w1   w2
        //
        // So the formula is (w2 * h1) / w1
    
        $height = floor(($width * imagesy($this->img)) / imagesx($this->img));
    
        $this->resample($width, $height);
    }

    function resampleToRect($width, $height)
    {
        switch ($this->getOrientation()) {
            case "horizontal":
                $this->resampleToWidth($width);
                break;
        
            case "vertical":
                $this->resampleToHeight($height);
                break;
        
            case "square":
                $this->resample($width, $height);
                break;
        
            default:
        }
    }
    
    function resize($width, $height)
    {
        if (!$this->img) {
            $this->error = true;
            $this->status = "Attempted to resize without an image!";
            return false;
        }
    
        $tmp_img = imagecreatetruecolor($width, $height);
    
        imagecopyresized($tmp_img, $this->img, 0, 0, 0, 0, $width, $height, imagesx($this->img), imagesy($this->img));
        imagedestroy($this->img);
    
        $this->img = $tmp_img;
        return true;
    }
    
    function resizePercent($percent)
    {
        if (!$this->img) {
            $this->error = true;
            $this->status = "Attempted to resampel without an image!";
            return true;
        }
    
        $width = imagesx($this->img) * ($percent / 100);
        $height = imagesy($this->img) * ($percent / 100);
    
        $this->resize($width, $height);
    }
    
    function resizeToHeight($height)
    {
        // Calculate the ratio needed to keep the aspect correct
        // What we have is w1, h1, and h2. What we need is w2
        //
        // w1   x
        // -- = --
        // h1   h2
        //
        // So, the formula is (h2 * w1) / h1
    
        $width = floor(($height * imagesx($this->img)) / imagesy($this->img));
    
        $this->resize($width, $height);
    }
    
    function resizeToWidth($width)
    {
        // Same deal as above, except now we're looking for h2 instead of w2
        //
        // h1   x
        // -- = --
        // w1   w2
        //
        // So the formula is (w2 * h1) / w1
    
        $height = floor(($width * imagesy($this->img)) / imagesx($this->img));
    
        $this->resize($width, $height);
    }
    
    function resizeToRect($width, $height)
    {
        switch ($this->getOrientation()) {
            case "horizontal":
                $this->resizeToWidth($width);
                break;
        
            case "vertical":
                $this->resizeToHeight($height);
                break;
        
            case "square":
                $this->resize($width, $height);
                break;
        
            default:
        }
    }
    
    function create($width, $height)
    {
        $this->img = imagecreatetruecolor($width, $height);
        return true;
    }
    
    function destroy()
    {
        if (!$this->img) {
            $this->error = true;
            $this->status = "Attempted to destroy a non-allocated image!";
            return false;
        }
    
        imagedestroy($this->img);
        return true;
    }
    
    function recreate($width, $height)
    {
        if (!$this->destroy()) return false;
        if (!$this->create($width, $height)) return false;
    }

    function superimposeFromFile($file, $x1, $y1, $x2, $y2)
    {
        $img = new Image($file);
    
        $this->superimposeFromImage($img, $x1, $y1, $x2, $y2);
    }
    
    function superimposeFromImage($img, $x1, $y1, $x2, $y2)
    {
        imagecopyresampled($this->img, $img->img,
                           $x1, $y1,
                           0, 0,
                           $x2-$x1, $y2-$y1,
                           imagesx($img->img), imagesy($img->img));
    }
    
    function displayGIF()
    {
        if (!$this->img) {
            $this->error = true;
            $this->status = "Attempted to display a non-allocated image!";
            return false;
        }

        header("Content-type: image/gif");
        imagegif($this->img);
    
        return true;
    }
    
    function displayPNG()
    {
        if (!$this->img) {
            $this->error = true;
            $this->status = "Attempted to display a non-allocated image!";
            return false;
        }
    
        header("Content-type: image/png");
        imagepng($this->img);
    
        return true;
    }
    
    function displayJPG()
    {
        if (!$this->img) {
            $this->error = true;
            $this->status = "Attempted to display a non-allocated image!";
            return false;
        }
    
        header("Content-type: image/jpeg");
        imagejpeg($this->img);
    
        return true;
    }
    
    function saveAsJPG($file)
    {
        if (!$this->img) {
            $this->error = true;
            $this->status = "Attempted to save a JPG of a non-allocated image!";
            return false;
        }
    
        imagejpeg($this->img, $file);
        return true;
    }
      
    function display()
    {
        if (!$this->type) {
            $this->error = true;
            $this->status = "Attempted to display a non-loaded image -- I don't know the type! You tell me!";
            return false;
        }
    
        switch ($this->type) {
            case IMAGETYPE_GIF:
                return $this->displayGIF();
                break;
        
            case IMAGETYPE_PNG:
                return $this->displayPNG();
                break;
        
            case IMAGETYPE_JPEG:
                return $this->displayJPG();
                break;
        
            default:
                $this->error = true;
                $this->status = "Whoa... Tried to display an unknown type of image (type is ". $this->type .")!";
                return false;
                break;
        }
    }
    
}
