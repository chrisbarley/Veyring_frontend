<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
/**
 * Description of DefaultController
 *
 * @author delio
 * @Route("/page")
 */
class DefaultController extends Controller{
   
    /**
     * @Route("/", methods={"GET"})
     */
    public function index()
    {
        return $this->render('page.html.twig');
    }
}
