<?php
    trait administradores {
		public function page_ajax_administradores(){
			if(isset($_POST["data"])){
				// $this->dbg($_POST);
				if($_POST["data"][1] == "erase"){
					$this->database()->deleteWhere("administradores", "id = {$_POST["data"][0]}");
				}
				exit;
			}
		}
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
			// $this->dbg($this->permissao("crud_admins"));
            $content->minify = true;

            if($this->post())return $this->ajax_administradores();

            if(
                parent::url(2) == "apagar" && (!empty(parent::url(1)) || (string)parent::url(1) == "0") &&
                count(parent::database()->query("administradores", "id = " . ($query = (string)parent::url(1)))) > 0
            ){
                exit(parent::database()->deleteWhere("administradores", "id={$query}"));
            }

            $id = parent::database()->newID("administradores");

            $size_form = 6;

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
					if(!$me && !$this->permissao("crud_admins")){
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

                } elseif(parent::url(1) == "listar" && $this->permissao("crud_admins")){
					$admins = $this->database()->query("administradores", "@ID > -1");
					$niveis = [
						"na_ctrl_total" => "Controle Total",
						"na_crud_cli" => "Criar e modificar Clientes",
						"na_pagamentos" => "Gerenciar Pagamentos",
						"na_boletos" => "Gerenciar Boletos",
						"na_inadimplentes" => "Visualizar Inadimplentes",
						"na_recibo_cli" => "Gerar Recibos para Clientes",
						"na_contratos" => "Gerar Contratos",
						"na_requerimentos" => "Gerar Requerimentos",
						"na_procuracoes" => "Gerar Procurações",
						"na_crud_vend" => "Criar e modificar Vendedores",
						"na_recibo_ven" => "Gerar Recibos para Vendedores",
						"na_crud_imov" => "Gerenciar Imóveis",
						"na_crud_fluxo" => "Gerenciar Financeiro/Fluxo de Caixa",
						"na_crud_admins" => "Criar e modificar usuários"
					];
					foreach(array_keys($admins) as $k){
						if(!$this->permissao("ctrl_total") && isset($admins[$k]["na_ctrl_total"]) && $admins[$k]["na_ctrl_total"] === "true"){
							unset($admins[$k]);
						} else {
							$admins[$k]["permissoes"] = [];
							foreach($niveis as $nivel => $txt){
								if(isset($admins[$k][$nivel]) && $admins[$k][$nivel] == "true"){
									$admins[$k]["permissoes"][] = $txt;
								}
							}
							$admins[$k]["permissoes"] = implode(", ", $admins[$k]["permissoes"]);
						}
					}
                    $btnTxt          = "Administrador";
                    $keyword         = "administradores";
                    $db              = $admins;
                    $titulos         = "Nome,Permissões";
                    $dados           = "nome,permissoes";
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
				if(!$me && !$this->permissao("crud_admins")){
					$this->page_meus_dados($content);
					exit;
				}
			}

            $content = $this->simple_loader($content, "administrador", $vars);

            echo $content->getCode();
        }

        function page_meus_dados($content){
            $this->page_administradores($content, true);
        }
    }
