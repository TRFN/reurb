<?php

	trait vendedores {

		/* Relatorio */

		function data_atual($time=-1){
			setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
			date_default_timezone_set('America/Sao_Paulo');
			return ([
				ucfirst(strftime('%A', $time==-1?strtotime('today'):strtotime($time))),
				ucfirst(strftime('%d', $time==-1?strtotime('today'):strtotime($time))),
				ucfirst(strftime('%B', $time==-1?strtotime('today'):strtotime($time))),
				ucfirst(strftime('%Y', $time==-1?strtotime('today'):strtotime($time)))
			]);
		}

		function page_relatorio_vendedor(UITemplate $content){
			// $this->data_atual();
			$content->minify = true;

			$vars = [];
			$vars["myid"]    = "";
			$vars["data"]    = "";
			$vars["modo"]    = "";
			$vars["titulo"]  = "";
			$vars["vendedor"] = "[]";
			$vars["texto_botao"] = "";

			switch($this->url(1)){
				case "exportar":
					$imoveis = $this->database()->query("imoveis", "id > -1");
					$vendas = [];
					foreach($imoveis as $imovel){
						if($imovel["vendedor"]==$this->url(2)){
							$cli = $this->database()->query("clientes", "id = {$imovel["cliente"]}");
							$cli = $cli[0];
							$imovel["cliente"] = $cli["nome"];
							$imovel["cliente-id"] = $cli["id"];
							$imovel["imovel"] = $imovel["id"];
							$ven = $this->database()->query("vendedores", "id = {$imovel["vendedor"]}");
							$ven = $ven[0];
							$ven = array_merge($cli, $ven);
							$vendas[] = array_merge($ven,$imovel);
						}
					}
					// $this->dbg($vendas);
					$vendedores = $vendas;
					foreach($vendedores as $k => $vendedor){
						$vendedores[$k]["valor"] = "R$ " . number_format($comissao=((float)((int)str_replace(",",".",str_replace(".","",str_replace("R$ ", "",$vendedores[$k]["valor-venda"]))) / 100) * (int)$vendedores[$k]["comissao"]), 2, ",", ".");
						// $this->dbg($comissao);
						$vendedores[$k]["comissao"] = "{$vendedores[$k]["comissao"]}%";
						$vendedores[$k]["imovel-local"] = "<a target=_blank href='/imovel/editar/{$vendedores[$k]["imovel"]}/' target=_blank>{$vendedores[$k]["rua"]}, {$vendedores[$k]["numero"]} {$vendedores[$k]["complemento"]} -  {$vendedores[$k]["bairro"]}<br>{$vendedores[$k]["cidade"]} - {$vendedores[$k]["estado"]}</a>";
						$vendedores[$k]["cliente"] = "<a target=_blank href='/cliente/editar/{$vendedores[$k]["cliente-id"]}/' target=_blank>{$vendedores[$k]["cliente"]}</a>";
						$vendedores[$k]["acao"] = "<input type=checkbox data-imovel-id='{$vendedores[$k]["imovel"]}' data-comissao='{$comissao}' data-perc='{$vendedores[$k]["comissao"]}' data-nome='{$vendedores[$k]["nome"]}' class='incluir_dado' />";
						$vendedores[$k]["valor"] = "{$vendedores[$k]["valor"]}&nbsp;({$vendedores[$k]["comissao"]})";
					}
					$ven = $this->database()->query("vendedores", "id = {$this->url(2)}");
					$keyword         = "vendedor";
					$btnTxt          = "Vendedor";
					$db              = $vendedores;
					$titulos         = "Cliente,Imovel,Valor Comissão,<label class='d-block'><input data-skin=white data-toggle=m-tooltip data-placement=top title='' data-original-title='Selecionar Todos' type=checkbox class='incluir_todos float-left d-block' />&nbsp;Incluir no Recibo</label>";
					$dados           = "cliente,imovel-local,valor,acao";
					$keyid           = "id";
					$titulo          = "Relatório dos Vendedores";

					exit($this->_tablepage($content,$keyword,$titulos,$dados,$keyid,$titulo,$db,$btnTxt,"not",false,"vendedor/exportar","",["nome"=>$ven[0]["nome"], "ret" => ($this->url(3) == "vendedor" ? "vendedor":"relatorio_vendedor"), "data" => date("Y-m-d")])->getCode());
				break;
				default:
					$vendedores = $this->database()->query("vendedores", "id > -1");
					foreach($vendedores as $k => $vendedor){
						$vendedores[$k]["comissao"] = "{$vendedores[$k]["comissao"]}%";
						$vendedores[$k]["acao"] = "<a class='btn m-btn btn-outline-dark' href='/relatorio_vendedor/exportar/{$vendedores[$k]["id"]}/'><i class='la la-list'></i>&nbsp;Acessar</a>";
						$vendedores[$k]["nome"] = "<a target=_blank href='/vendedor/editar/{$vendedores[$k]["id"]}/' target=_blank>{$vendedores[$k]["nome"]}</a>";
					}
					$btnTxt          = "Vendedor";
					$keyword         = "vendedor";
					$db              = $vendedores;
					$titulos         = "Nome,E-mail,Comissão,Recibo";
					$dados           = "nome,email,comissao,acao";
					$keyid           = "id";
					$titulo          = "Relatório dos Vendedores";

					exit($this->_tablepage($content,$keyword,$titulos,$dados,$keyid,$titulo,$db,$btnTxt,"not",false,"vendedor/texp")->getCode());
				break;
			}
		}

		function page_relatorio_vendedor_imprimir(UITemplate $content){
			// $this->dbg($_POST);
			$content->uiTemplate("recibo-vendedor");
			$total = $_POST['comissao'];
			$data_atual = $this->data_atual($_POST['data']);
			$content->applyVars(array(
				"total" => "R$ " . number_format($_POST['comissao'], 2, ",", "."),
				"total-extenso" => clsTexto::valorPorExtenso($total, true, false),
				"n-vendas" => $_POST['qtd'],
				"n-vendas-extenso" => clsTexto::valorPorExtenso($_POST['qtd'], false, true),
				"comissao" => $_POST["perc"],
				"nome" => $_POST["nome"],
				"dia" => "{$data_atual[1]}",
				"mes" => "{$data_atual[2]}",
				"ano" => "{$data_atual[3]}"
			));
			echo $content->getCode();
		}

		/* AJAX */

		function get_vendedores($get = -1){
			return $this->database()->query("vendedores", $get === -1 ? "id > -1":"id = {$get}");
		}

		function page_vendedores_atualizados(){
			exit('<option selected readonly value="not">Selecione um vendedor</option>' . $this->transform_to_option($this->get_vendedores()));
		}

		function page_vendedores_comissao(){
			$this->json($this->get_vendedores($_POST["v"])[0]["comissao"]);
		}

		/* Cadastros */

		function page_vendedor(UITemplate $content){
            $content->minify = true;

			$vars = [];
			$vars["myid"]    = "";
			$vars["data"]    = "";
			$vars["modo"]    = "";
			$vars["titulo"]  = "";
			$vars["vendedor"] = "[]";
			$vars["texto_botao"] = "";

			switch($this->url(1)){
				case "novo":
					$vars["myid"] = $this->database()->newID("vendedores");
					$vars["data"] = date("Y-m-d");
					$vars["modo"] = "criar";
					$vars["titulo"] = "Adicionar Novo Vendedor";
					$vars["texto_botao"] = "Salvar";

					$content = $this->simple_loader($content, "vendedor/formulario", $vars);

					echo $content->getCode();
				break;
				case "editar":
					$vars = [];
					$vars["myid"] = $this->url(2);
					$vars["data"] = date("Y-m-d");
					$vars["modo"] = "mod";
					$vars["titulo"] = "Modificar Este Vendedor";
					$vars["texto_botao"] = "Modificar";

					$id = $vars["myid"];
					$vars["vendedor"] = json_encode($this->database()->query("vendedores", "id = {$id}"));

					$content = $this->simple_loader($content, "vendedor/formulario", $vars);

					echo $content->getCode();
				break;
				default:
					$vendedores = $this->database()->query("vendedores", "id > -1");
					foreach($vendedores as $k => $vendedor){
						$vendedores[$k]["comissao"] = "{$vendedores[$k]["comissao"]}%";

						$vendedores[$k]["acao"] = "<a class='btn m-btn btn-outline-dark' href='/relatorio_vendedor/exportar/{$vendedores[$k]["id"]}/vendedor/'><i class='  la-1x  la la-list-alt'></i>&nbsp;Acessar</a>";
					}
					$btnTxt          = "Vendedor";
					$keyword         = "vendedor";
					$db              = $vendedores;
					$titulos         = "Nome,CPF,Comissão,Recibo";
					$dados           = "nome,doc,comissao,acao";
					$keyid           = "id";
					$titulo          = "Gerir Vendedores Cadastrados";

					exit($this->_tablepage($content,$keyword,$titulos,$dados,$keyid,$titulo,$db,$btnTxt)->getCode());
				break;
			}
        }

		function page_ajax_vendedores(){
			$id       = isset($_POST["data"][0]) ? $_POST["data"][0]:"";
			$vendedor = isset($_POST["data"][1]) ? $_POST["data"][1]:"";
			$modo     = isset($_POST["data"][2]) ? $_POST["data"][2]:"";

			if($modo == "criar"){
				$vendedor["id"] = $this->database()->newID("vendedores");

				$this->database()->push("vendedores", array($vendedor));
			} elseif($modo == "mod"){
				$vendedor["id"] = $id;
				$this->database()->setWhere("vendedores", "id = {$id}", $vendedor);
			} elseif($vendedor == "erase"){
				$this->database()->deleteWhere("vendedores", "id = {$id}");
			}
			$this->json(true);
		}


	}

?>
