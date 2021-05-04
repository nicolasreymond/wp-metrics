<?php


class MyPlugin{  
    
      private $my_plugin_screen_name;  
      private static $instance;  
    
      static function GetInstance()  
      {  
            
          if (!isset(self::$instance))  
          {  
              self::$instance = new self();  
          }  
          return self::$instance;  
      }  
        
      public function PluginMenu()  
      {  
       $this->my_plugin_screen_name = add_menu_page(  
                                        'My Plugin',   
                                        'My Plugin',   
                                        'manage_options',  
                                        __FILE__,   
                                        array($this, 'RenderPage'),   
                                        plugins_url('/img/icon.png',__DIR__)  
                                        );  
      }  
        
      public function RenderPage(){  
       ?>  
       <div class='wrap'>  
        <h2>custom form</h2>  
        <div class="main-content">  
  
  
        <form class="form-basic" method="post" action="#">  
  
            <div class="form-title-row">  
                <h1>Administration</h1>  
            </div>  
  
            
            <div class="form-row">  
                <label>  
                    <span>Set password</span>  
                    <input type="text" name="password">  
                </label>  
            </div>  
            
  
            <div class="form-row">  
                <button type="submit">Submit Form</button>  
            </div>  
  
        </form>  
  
    </div>  
          
       </div>  
       <?php  
      }  
  
      public function InitPlugin()  
      {  
           add_action('admin_menu', array($this, 'PluginMenu'));  
      }  
    
 }  
   
$MyPlugin = MyPlugin::GetInstance();  
$MyPlugin->InitPlugin();  
?> 