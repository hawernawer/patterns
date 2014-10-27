<?php
/**
 * Created by PhpStorm.
 * User: alejandro
 * Date: 05/10/14
 * Time: 13:05
 */
define("CONVERSION_RATE_MM_TO_PX",1.27);

$mm_to_px = 1.27;//1.31445;
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once('fpdf/fpdf.php');
require_once('fpdi/fpdi.php');

if($_POST){
    try
    {
        function paintBaseA4($im){
            //IN THIS FUNCTION WE WILL PAINT THE BASIC MARGINS FOR THE A4 AND ANYTHING NEEDED BY DEFAULT
            // IT'S BEING ASSUMED THAT THIS $DRAW BELONGS TO AN IMAGE OBJECT OF THE PROPER SIZE (AN A4)

            $draw = new ImagickDraw();
            $draw->setStrokeColor(new ImagickPixel("#FFFFFF"));
            $draw->setStrokeDashArray(array(20,20));
            $draw->line(200,200,200,2138);
            $draw->line(200,200,1445,200);
            $draw->line(200,2138,1445,2138);
            $draw->line(1445,200,1445,2138);
            $im->drawImage( $draw );


        }


       function finalStep($file){
           $pdf = new FPDI();

           $pageCount = $pdf->setSourceFile($file);

           for($i=0; $i<$pageCount; $i++){
               $pdf->AddPage();
               $tplIdx = $pdf->importPage($i+1, '/MediaBox');
               $pdf->useTemplate($tplIdx, 0, 0, 0);
           }

           $pdf->output();

           header('Content-Description: File Transfer');
           header('Content-Type: application/octet-stream');
           header('Content-Disposition: attachment; filename='.basename($pdf));
           header('Content-Transfer-Encoding: binary');
           header('Expires: 0');
           header('Cache-Control: must-revalidate');
           header('Pragma: public');
           header('Content-Length: ' . filesize($pdf));

           readfile($pdf);
       }


        /*** a new imagick object ***/
        $im = new Imagick();
        $im->setResolution(200,200);
        /*** a new image ***/
       // $im->newImage(1645,2338, new ImagickPixel( "white" ) );
        $w = 1645;
        $h = 2338;
        // at this point, we imagine that we know how big will be the image, in this case  we will do an A3

        $im->newImage(2*$w,$h, new ImagickPixel( "white" ) );



        /*** Now lets draw some stuff ***/
        $draw = new ImagickDraw();
        $file = '/tmp/rectangle3.pdf';

        $draw->line(0,800,2700/$mm_to_px,800);
        $draw->annotation(15+(2700/$mm_to_px),800,"27");

        $im->drawImage( $draw );


        //  Now we have a A3 with a line that need to be cropped and pasted into two new images


        /*
         * Two new images to contain the regions we extract
         *
         * */
        $im1 = new Imagick();
        $im1->setResolution(200,200);
        $im2 = new Imagick();
        $im2->setResolution(200,200);



        //  We get a copy of the image 200 px less (this is the space we will add as a margin later)
        $im1 = $im->getImageRegion($w-400,$h-400,0,0);
        $im2 = $im->getImageRegion($w-400,$h-400,$w-400,0);

        // Now we extend the Images to the proper size of an A4 adding the 200px margins. We start in -100 to add 200

        $im1->extentImage($w,$h,-200,-200);
        $im2->extentImage($w,$h,-200,-200);
        //This reset the value of the page to be able to draw on it again
        $im1->setImagePage(0, 0, 0, 0);
        $im2->setImagePage(0, 0, 0, 0);

        paintBasea4($im1);
        paintBasea4($im2);

        // THIS IS TEMPORAL, I think we can use fpdf to merge and add numbers
        // but in the meanwhile, we write directly the file into the disk, we should do the same in a tmp folder
        // and merge them before erasing the tmp folder

        $im1->setImageFormat( "pdf" );
        $im1->writeImage('/tmp/name1.pdf');

        $im2->setImageFormat( "pdf" );
        $im2->writeImage('/tmp/name2.pdf');




        /*
         * Merge all files into one
         */

        $fileArray= array("/tmp/name1.pdf","/tmp/name2.pdf",);

        $datadir = "/tmp/";
        $outputName = $datadir."merged.pdf";

        $cmd = "gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=$outputName ";
        //Add each pdf file to the end of the command
        foreach($fileArray as $file) {
            $cmd .= $file." ";
        }
        $result = shell_exec($cmd);



        // Add

       finalStep("/tmp/merged.pdf");

        exit;

//        echo 'Image Created';
    }
    catch(Exception $e)
    {
        echo $e->getMessage();
    }

}

