<?php

	trait imoveis {
		function page_imovel(UITemplate $content){
            $content->minify = true;

			$vars = [];
			$vars["myid"]    = "";
			$vars["data"]    = "";
			$vars["modo"]    = "";
			$vars["titulo"]  = "";
			$vars["imovel"] = "[]";
			$vars["texto_botao"] = "";
			$vars["vendedores"] = $this->transform_to_option($this->get_vendedores());
			$vars["clientes"] = $this->transform_to_option($this->get_clientes());

			switch($this->url(1)){
				case "novo":
					$vars["myid"] = $this->database()->newID("imoveis");
					$vars["data"] = date("Y-m-d");
					$vars["modo"] = "criar";
					$vars["titulo"] = "Adicionar Novo Imovel";
					$vars["texto_botao"] = "Salvar";

					$content = $this->simple_loader($content, "imovel/formulario", $vars);

					echo $content->getCode();
				break;
				case "editar":
					$vars["myid"] = $this->url(2);
					$vars["data"] = date("Y-m-d");
					$vars["modo"] = "mod";
					$vars["titulo"] = "Modificar Este Imóvel";
					$vars["texto_botao"] = "Modificar";

					$id = $vars["myid"];
					$vars["imovel"] = json_encode($this->database()->query("imoveis", "id = {$id}"));

					$content = $this->simple_loader($content, "imovel/formulario", $vars);

					echo $content->getCode();
				break;
				default:
					$btnTxt          = "Imóvel";
					$keyword         = "imovel";
					$db              = "imoveis";
					$titulos         = "CEP,Guia,Valor de Venda";
					$dados           = "cep,guia,valor-venda";
					$keyid           = "id";
					$titulo          = "Gerir Imóveis Cadastrados";

					exit($this->_tablepage($content,$keyword,$titulos,$dados,$keyid,$titulo,$db,$btnTxt)->getCode());
				break;
			}
        }

		function page_ajax_imoveis(){
			$id       = isset($_POST["data"][0]) ? $_POST["data"][0]:"";
			$imovel   = isset($_POST["data"][1]) ? $_POST["data"][1]:"";
			$modo     = isset($_POST["data"][2]) ? $_POST["data"][2]:"";

			if($modo == "criar"){
				$imovel["id"] = $id;
				$this->database()->push("imoveis", array($imovel));
			} elseif($modo == "mod"){
				$imovel["id"] = $id;
				$this->database()->setWhere("imoveis", "id = {$id}", $imovel);
			} elseif($imovel == "erase"){
				$this->database()->deleteWhere("imoveis", "id = {$id}");
			}
			$this->json(true);
		}
	}

?>
