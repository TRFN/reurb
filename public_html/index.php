<?php
    include "lwdk/engine/main.php";

    class bootsys extends lwdk {
        use route;

        function __construct(){
			$this->setup();

            $this->addApp(new admin_panel, $this);

            $this->renderApp();
        }
    }

    new bootsys;
?>
