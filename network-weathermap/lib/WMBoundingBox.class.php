<?php

class WMBoundingBox
{
    private $minimumX;
    private $maximumX;
    private $maximumY;
    private $minimumY;

    public function __construct()
    {
        $this->minimumX = null;
        $this->maximumX = null;
        $this->maximumY = null;
        $this->minimumY = null;
    }

    public function addWMPoint($point)
    {
        $this->addPoint($point->x, $point->y);
    }

        public function addPoint($x, $y)
        {
        if (is_null($this->minimumX) || $x < $this->minimumX) {
            $this->minimumX = $x;
        }
        if (is_null($this->maximumX) || $x > $this->maximumX) {
            $this->maximumX = $x;
        }
        if (is_null($this->minimumY) || $y < $this->minimumY) {
            $this->minimumY = $y;
        }
        if (is_null($this->maximumY) || $y > $this->maximumY) {
            $this->maximumY = $y;
        }
        }

        public function getBoundingRectangle()
        {
            if (null === $this->minimumX) {
                throw new WMException("No Bounding Box until points are added");
            }
            return new WMRectangle($this->minimumX, $this->minimumY, $this->maximumX, $this->maximumY);
        }

        public function __toString()
        {
            try {
                $r = $this->getBoundingRectangle();
            } catch (WMException $e) {
                $r = "[Empty BBox]";
            }
            return "$r";
        }
}
