<?php
    trait administradores {
        private function ajax_administradores(){
            try{
                header("Content-Type: application/json");
                $id = $_POST["id"];
                if(!empty($_POST["senha"])){
                    $_POST["senha"] = md5($_POST["senha"]);
                } else {
                    unset($_POST["senha"]);
                }
                $query = $this->database()->query("administradores", "id = {$id}");
                if(!count($query)){
                    $this->database()->push("administradores",array($_POST),"log_remove");
                } else {
                    $this->database()->setWhere("administradores","id = {$id}",$_POST);
                }
            } catch(Exception $e){
                exit("false");
            }
            exit("true");
        }

        function page_administradores($content,$me=false){

            $content->minify = true;

            if($this->post())return $this->ajax_administradores();

            if(
                parent::url(2) == "apagar" && (!empty(parent::url(1)) || (string)parent::url(1) == "0") &&
                count(parent::database()->query("administradores", "id = " . ($query = (string)parent::url(1)))) > 0
            ){
                exit(parent::database()->deleteWhere("administradores", "id = {$query}"));
            }

            $id = parent::database()->newID("administradores");

            $size_form = 4;

            $vars = array(
                "id"        => $id,
                "botao-txt" => "Criar Novo Usuário",
                "TITLE"     => "Adicionar Usuário",
                "nome"      => "",
                "email"     => "",
                "size_l"    => round((12-$size_form)/2)-1,
                "size_r"    => $size_form,
                "acao"      => "criar",
                "page"      => "administradores"
            );

            if(!empty(parent::url(2)) || (string)parent::url(2) == "0" || $me || parent::url(1) == "listar"){
                $searchID = $me ? $this->admin_sessao()->id:(string)parent::url(2);
                if(count($query = parent::database()->query("administradores", "id = " . $searchID)) > 0){
					if(!$me && !$this->nivelacesso("Administrador")){
						$this->page_meus_dados($content);
						exit;
					}
                    $vars["TITLE"]      = ($me?"Alterar seus dados":"Modificar Usuário");
                    $vars["botao-txt"]  = "Salvar o que foi modificado";
                    $vars["acao"]       = "modificar";

                    foreach($query[0] as $id=>$val){
                        $vars[$id] = is_array($val) ? json_encode($val):$val;
                    }

                    unset($vars[0]);

                } elseif(parent::url(1) == "listar" && $this->nivelacesso("Administrador")){
                    $btnTxt          = "Administrador";
                    $keyword         = "administradores";
                    $db              = "administradores";
                    $titulos         = "Nome,E-mail,Nível de Acesso";
                    $dados           = "nome,email,nivel_acesso";
                    $keyid           = "id";
                    $titulo          = "Gerir Acesso do Sistema";

                    exit($this->_tablepage($content,$keyword,$titulos,$dados,$keyid,$titulo,$db,$btnTxt,"id != ".$this->admin_sessao()->id)->getCode());
                } else {
					if(!$me){
						$this->page_meus_dados($content);
						exit;
					}
				}
            } else {
				if(!$me && !$this->nivelacesso("Administrador")){
					$this->page_meus_dados($content);
					exit;
				}
			}

            $content = $this->simple_loader($content, strtolower($this->nivelacesso()), $vars);

            echo $content->getCode();
        }

        function page_meus_dados($content){
            $this->page_administradores($content, true);
        }
    }
