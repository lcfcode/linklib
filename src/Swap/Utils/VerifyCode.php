<?php
/**
 * @link https://gitee.com/lcfcode/linklib
 * @link https://github.com/lcfcode/linklib
 */

namespace Swap\Utils;

use function imagecreate;

class VerifyCode
{
    private $code;
    private $length;
    private $width;
    private $height;

    public function __construct($width = 120, $len = 4, $height = 0)
    {
        $this->length = $len;
        $this->width = $width;
        $this->height = $height;
        $this->code = $this->createCode($len);
    }

    /**
     * @return string
     * @user LCF
     * @date 2019/3/15 22:40
     * 获取验证码内容
     */
    public function getCode()
    {
        return $this->code;
    }

    private function createCode($len)
    {
        $str = '';
        for ($i = 0; $i < $len; $i++) {
            $str .= mt_rand(0, 9);
        }
        return $str;
    }

    /**
     * @user LCF
     * @date 2019/3/15 22:39
     * 获取验证码图片
     */
    public function verifyPng()
    {
        $height = $this->height;
        $width = intval($this->width);
        $length = intval($this->length);
        $code = $this->code;
        if ($height == 0) {
            $height = intval($width / 3);
        }
        $img = imagecreate($width, $height);
        imagecolorallocate($img, 255, 255, 255);
        $pointNum = $width * 2;
        if ($pointNum > 250) {
            $pointNum = 250;
        }
        $lineNum = $length;
        if ($lineNum > 4) {
            $lineNum = 4;
        }
        //随机颜色
        $colorArr = [];
        for ($i = 0; $i < $pointNum; $i++) {
            $colorArr[] = imagecolorallocate($img, mt_rand(50, 200), mt_rand(50, 200), mt_rand(50, 200));
        }
        for ($i = 0; $i < $width; $i++) {
            imagesetpixel($img, mt_rand(0, $width), mt_rand(0, $height), $colorArr[mt_rand(0, $pointNum - 1)]);
            imagesetpixel($img, mt_rand(0, $width), mt_rand(0, $height), $colorArr[mt_rand(0, $pointNum - 1)]);
        }
        for ($i = 0; $i < $lineNum; $i++) {
            imageline($img, mt_rand(0, ($width / 2) - $height), mt_rand(0, $height), mt_rand(($width / 2) + $height, $width), mt_rand(0, $height), $colorArr[mt_rand(0, $pointNum - 1)]);
        }
        for ($i = 0; $i < $lineNum - 1; $i++) {
            imagearc($img, mt_rand(-10, $width + 10), mt_rand(-10, $height + 10), mt_rand($height, $width), mt_rand($height, $width), mt_rand(40, 50), mt_rand(30, 40), $colorArr[mt_rand(0, $pointNum - 1)]);
        }

        for ($i = 0; $i < $length; $i++) {
            $yRand = intval($height / 3);
            $charX = (($i * $width) / $length) + mt_rand(($length - $i), $yRand);
            $charY = mt_rand($yRand, $yRand + $length);
            imagestring($img, 5, $charX, $charY, $code[$i], $colorArr[mt_rand(0, $pointNum - 1)]);
        }
        ob_clean();
        imagepng($img);
        $content = ob_get_clean();
        imagedestroy($img);
        return $content;
    }
}
