
   <?php $this->startSection('head') ?>
       <style>
          
           .welcome {
               text-align: center;
               margin-top: 50px;
           }
           .welcome h1 {
               color: #333;
           }
           .welcome p {
               color: #666;
           }
       </style>
   <?php $this->endSection() ?> 


    <div class="container">
        <div class="welcome">
            <h1>Welcome to Your Application</h1>
            <p>This page is using the default layout system.</p>    
        </div>

        <h1><?= htmlspecialchars($title) ?></h1>
        <p><?= htmlspecialchars($message) ?></p>
        <p><a href="/about">About Us</a></p>
    </div>

