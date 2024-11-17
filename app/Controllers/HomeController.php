<?php

namespace App\Controllers;

use Core\Controller;

class HomeController extends Controller
{
    public function index()
    {
        $data = [
            'title' => 'Welcome to Your Framework',
            'message' => 'This is a sample page rendered using the MVC framework.'
        ];
        
        return $this->render('home/index', $data);
    }

    public function about()
    {
        return $this->render('home/about', [
            'title' => 'About Us',
            'content' => 'This is the about page.'
        ]);
    }
}
