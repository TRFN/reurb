<?php

	trait clientes {
		function get_clientes($get = -1){
			return $this->database()->query("clientes", $get === -1 ? "id > -1":"id = {$get}");
		}

		function page_cli_list(){
			$this->json($this->get_clientes());
		}

		function transform_to_option($in, $txt = "nome", $id = "id"){
			$out = "";

			foreach($in as $data){
				$out .= "<option value='{$data[$id]}'>{$data[$txt]}</option>";
			}

			return $out;
		}

		function page_contrato_cliente(UITemplate $content){
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
						if($imovel["cliente"]==$this->url(2)){
							$cli = $this->database()->query("clientes", "id = {$imovel["cliente"]}");
							$cli = $cli[0];
							$ven = $this->database()->query("vendedores", "id = {$imovel["vendedor"]}");
							$ven = $ven[0];

							$imovel["vendedor"] = $ven["nome"];
							$imovel["vendedor-id"] = $ven["id"];
							$imovel["imovel"] = $imovel["id"];

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
						$vendedores[$k]["vendedor"] = "<a target=_blank href='/vendedor/editar/{$vendedores[$k]["vendedor-id"]}/' target=_blank>{$vendedores[$k]["vendedor"]}</a>";
						$vendedores[$k]["acao"] = "<button data-id='{$this->url(2)}' data-imov='{$vendedores[$k]["imovel"]}' class='imprimir btn btn-dark m-btn py-4'><i class='fa-2x  la la-print'></i></button>";
						$vendedores[$k]["valor"] = "{$vendedores[$k]["valor"]}&nbsp;({$vendedores[$k]["comissao"]})";
					}
					$cli = $this->database()->query("clientes", "id = {$this->url(2)}");
					$keyword         = "cliente";
					$btnTxt          = "Cliente";
					$db              = $vendedores;
					$titulos         = "Vendedor,Imovel,Valor Total,Imprimir";
					$dados           = "vendedor,imovel-local,valor-venda,acao";
					$keyid           = "id";
					$titulo          = "Contrato dos Clientes";

					exit($this->_tablepage($content,$keyword,$titulos,$dados,$keyid,$titulo,$db,$btnTxt,"not",false,"cliente/tabela","",["nome"=>$cli[0]["nome"], "titulo" => "Gerar Contrato", "ret" => ($this->url(3) == "cliente" ? "cliente":"contrato_cliente"), "data" => date("Y-m-d"), "id" => $this->url(2), "area" => "contrato_cliente", "d-valor" => "d-none"])->getCode());
				break;
				default:
					$vendedores = $this->database()->query("clientes", "id > -1");
					foreach($vendedores as $k => $vendedor){
						$vendedores[$k]["acao"] = "<a class='btn m-btn btn-outline-dark' href='/contrato_cliente/exportar/{$vendedores[$k]["id"]}/'><i class='la la-list'></i>&nbsp;Gerar Contratos</a>";
						$vendedores[$k]["nome"] = "<a target=_blank href='/cliente/editar/{$vendedores[$k]["id"]}/' target=_blank>{$vendedores[$k]["nome"]}</a>";
						$vendedores[$k]["tels"] = "<p>{$vendedores[$k]["tel1"]}</p><p>{$vendedores[$k]["tel2"]}</p><p>{$vendedores[$k]["tel3"]}</p>";
					}
					$btnTxt          = "Cliente";
					$keyword         = "cliente";
					$db              = $vendedores;
					$titulos         = "Nome,CPF,RG,Telefones,Contratos";
					$dados           = "nome,doc,rg,tels,acao";
					$keyid           = "id";
					$titulo          = "Gestão de Contratos";

					exit($this->_tablepage($content,$keyword,$titulos,$dados,$keyid,$titulo,$db,$btnTxt,"not",false,"vendedor/texp")->getCode());
				break;
			}
		}

		function page_contrato_cliente_imprimir(UITemplate $content){
			$content->uiTemplate("contrato-cliente");
			// $total = $_POST['comissao'];
			$data_atual = $this->data_atual($_POST['data']);
			$cli = $this->database()->query("clientes", "id = {$_POST["id"]}");
			$imv = $this->database()->query("imoveis", "id = {$_POST["imv"]}");
			// $this->dbg(clsTexto::valorPorExtenso($imv[0]["tamanho"], true, false, "metros"));
			$imv[0]["valor-venda-ext"] = clsTexto::valorPorExtenso($imv[0]["valor-venda-real"], true, false);
			$imv[0]["valor-venda-desc-ext"] = clsTexto::valorPorExtenso($imv[0]["valor-venda-desconto"], true, false);
			$parc_ext = clsTexto::valorPorExtenso(preg_replace("/[^0-9]/","",$imv[0]["forma-pgto"]), false, false);
			$pre_parc = $imv[0]["valor-venda-desconto"] == $imv[0]["valor-venda"] ? "Excepcionalmente, em caráter de preço especial com desconto, o CONTRATANTE pagará ao CONTRATADO, por este contrato, o valor de {$imv[0]["valor-venda-desconto"]} ({$imv[0]["valor-venda-desc-ext"]}) da seguinte forma:&nbsp;":"";
			$content->applyVars(array_merge($cli[0],$imv[0],array(
				"dia" => "{$data_atual[1]}",
				"mes" => "{$data_atual[2]}",
				"ano" => "{$data_atual[3]}",
				"texto-parcela" => $pre_parc . ($imv[0]["forma-pgto"] !== "vz1-1" ? "O CONTRATANTE se obriga a pagar todas as {$parc_ext} parcelas, mesmo após a conclusão dos serviços, por parte da CONTRATADA, ou seja, mesmo depois de emitida, pela prefeitura, a Certidão de Regularização Fundiária – CRF.":"O CONTRATANTE se obriga a pagar o valor proposto.")
			)));
			echo $content->getCode();
		}

		function page_procuracao_cliente(UITemplate $content){
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
						if($imovel["cliente"]==$this->url(2)){
							$cli = $this->database()->query("clientes", "id = {$imovel["cliente"]}");
							$cli = $cli[0];
							$ven = $this->database()->query("vendedores", "id = {$imovel["vendedor"]}");
							$ven = $ven[0];

							$imovel["vendedor"] = $ven["nome"];
							$imovel["vendedor-id"] = $ven["id"];
							$imovel["imovel"] = $imovel["id"];

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
						$vendedores[$k]["vendedor"] = "<a target=_blank href='/vendedor/editar/{$vendedores[$k]["vendedor-id"]}/' target=_blank>{$vendedores[$k]["vendedor"]}</a>";
						$vendedores[$k]["acao"] = "<button data-id='{$this->url(2)}' class='imprimir btn btn-dark m-btn py-4'><i class='fa-2x  la la-print'></i></button>";
						$vendedores[$k]["valor"] = "{$vendedores[$k]["valor"]}&nbsp;({$vendedores[$k]["comissao"]})";
					}
					$cli = $this->database()->query("clientes", "id = {$this->url(2)}");
					$keyword         = "cliente";
					$btnTxt          = "Cliente";
					$db              = $vendedores;
					$titulos         = "Vendedor,Imovel,Valor Total,Imprimir";
					$dados           = "vendedor,imovel-local,valor-venda,acao";
					$keyid           = "id";
					$titulo          = "Procuração dos Clientes";

					exit($this->_tablepage($content,$keyword,$titulos,$dados,$keyid,$titulo,$db,$btnTxt,"not",false,"cliente/tabela","",["nome"=>$cli[0]["nome"], "titulo" => "Gerar Procuração", "ret" => ($this->url(3) == "cliente" ? "cliente":"procuracao_cliente"), "data" => date("Y-m-d"), "id" => $this->url(2), "area" => "procuracao_cliente", "d-valor" => "m--hide"])->getCode());
				break;
				default:
					$vendedores = $this->database()->query("clientes", "id > -1");
					foreach($vendedores as $k => $vendedor){
						$vendedores[$k]["acao"] = "<a class='btn m-btn btn-outline-dark' href='/procuracao_cliente/exportar/{$vendedores[$k]["id"]}/'><i class='la la-list'></i>&nbsp;Gerar Procuração</a>";
						$vendedores[$k]["nome"] = "<a target=_blank href='/cliente/editar/{$vendedores[$k]["id"]}/' target=_blank>{$vendedores[$k]["nome"]}</a>";
						$vendedores[$k]["tels"] = "<p>{$vendedores[$k]["tel1"]}</p><p>{$vendedores[$k]["tel2"]}</p><p>{$vendedores[$k]["tel3"]}</p>";
					}
					$btnTxt          = "Cliente";
					$keyword         = "cliente";
					$db              = $vendedores;
					$titulos         = "Nome,CPF,RG,Telefones,Procurações";
					$dados           = "nome,doc,rg,tels,acao";
					$keyid           = "id";
					$titulo          = "Procuração dos Clientes";

					exit($this->_tablepage($content,$keyword,$titulos,$dados,$keyid,$titulo,$db,$btnTxt,"not",false,"vendedor/texp")->getCode());
				break;
			}
		}

		function page_procuracao_cliente_imprimir(UITemplate $content){
			// $this->dbg($_POST);
			$content->uiTemplate("procuracao-cliente");
			// $total = $_POST['comissao'];

			$data_atual = $this->data_atual($_POST['data']);
			$cli = $this->database()->query("clientes", "id = {$_POST["id"]}");
			$content->applyVars(array_merge($cli[0],array(
				"dia" => "{$data_atual[1]}",
				"mes" => "{$data_atual[2]}",
				"ano" => "{$data_atual[3]}"
			)));
			echo $content->getCode();
		}

		function page_requerimento_cliente(UITemplate $content){
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
						if($imovel["cliente"]==$this->url(2)){
							$cli = $this->database()->query("clientes", "id = {$imovel["cliente"]}");
							$cli = $cli[0];
							$ven = $this->database()->query("vendedores", "id = {$imovel["vendedor"]}");
							$ven = $ven[0];

							$imovel["vendedor"] = $ven["nome"];
							$imovel["vendedor-id"] = $ven["id"];
							$imovel["imovel"] = $imovel["id"];

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
						$vendedores[$k]["imovel-local"] = "<a target=_blank href='/imovel/editar/{$vendedores[$k]["imovel"]}/' target=_blank>{$vendedores[$k]["rua"]}, {$vendedores[$k]["numero"]} {$vendedores[$k]["complemento"]}  {$vendedores[$k]["bairro"]}<br>{$vendedores[$k]["cidade"]} - {$vendedores[$k]["estado"]}</a>";
						$vendedores[$k]["vendedor"] = "<a target=_blank href='/vendedor/editar/{$vendedores[$k]["vendedor-id"]}/' target=_blank>{$vendedores[$k]["vendedor"]}</a>";
						$vendedores[$k]["acao"] = "<button data-id='{$this->url(2)}' data-imov='{$vendedores[$k]["imovel"]}' class='imprimir btn btn-dark m-btn py-4'><i class='fa-2x  la la-print'></i></button>";
						$vendedores[$k]["valor"] = "{$vendedores[$k]["valor"]}&nbsp;({$vendedores[$k]["comissao"]})";
					}
					$cli = $this->database()->query("clientes", "id = {$this->url(2)}");
					$keyword         = "cliente";
					$btnTxt          = "Cliente";
					$db              = $vendedores;
					$titulos         = "Vendedor,Imovel,Serviço,Imprimir";
					$dados           = "vendedor,imovel-local,produto,acao";
					$keyid           = "id";
					$titulo          = "Requerimento dos Clientes";

					exit($this->_tablepage($content,$keyword,$titulos,$dados,$keyid,$titulo,$db,$btnTxt,"not",false,"cliente/tabela","",["nome"=>$cli[0]["nome"], "titulo" => "Gerar Requerimento", "ret" => ($this->url(3) == "cliente" ? "cliente":"requerimento_cliente"), "data" => date("Y-m-d"), "id" => $this->url(2), "area" => "requerimento_cliente", "d-valor" => "m--hide"])->getCode());
				break;
				default:
					$vendedores = $this->database()->query("clientes", "id > -1");
					foreach($vendedores as $k => $vendedor){
						$vendedores[$k]["acao"] = "<a class='btn m-btn btn-outline-dark' href='/requerimento_cliente/exportar/{$vendedores[$k]["id"]}/'><i class='la la-list'></i>&nbsp;Gerar Requerimento</a>";
						$vendedores[$k]["nome"] = "<a target=_blank href='/cliente/editar/{$vendedores[$k]["id"]}/' target=_blank>{$vendedores[$k]["nome"]}</a>";
						$vendedores[$k]["tels"] = "<p>{$vendedores[$k]["tel1"]}</p><p>{$vendedores[$k]["tel2"]}</p><p>{$vendedores[$k]["tel3"]}</p>";
					}
					$btnTxt          = "Cliente";
					$keyword         = "cliente";
					$db              = $vendedores;
					$titulos         = "Nome,CPF,RG,Telefones,Requerimentos";
					$dados           = "nome,doc,rg,tels,acao";
					$keyid           = "id";
					$titulo          = "Requerimento dos Clientes";

					exit($this->_tablepage($content,$keyword,$titulos,$dados,$keyid,$titulo,$db,$btnTxt,"not",false,"vendedor/texp")->getCode());
				break;
			}
		}

		function page_requerimento_cliente_imprimir(UITemplate $content){
			// $this->dbg($_POST);
			$content->uiTemplate("requerimento-cliente");
			// $total = $_POST['comissao'];

			$data_atual = $this->data_atual($_POST['data']);
			$cli = $this->database()->query("clientes", "id = {$_POST["id"]}");
			$imv = $this->database()->query("imoveis", "id = {$_POST["imv"]}");
			foreach($imv[0] as $dk=>$dv){
				$cli[0]["{$dk}-imov"] = $dv;
			}
			$cli[0]["tamanho-imov-ext"] = clsTexto::valorPorExtenso($cli[0]["tamanho-imov"], true, false, "metros");
			if($cli[0]["possuiedificacao-imov"]=="s"){
				$cli[0]["tamanho-edificacao-imov-ext"] = clsTexto::valorPorExtenso($cli[0]["tamanho-edificacao-imov"], true, false, "metros");

				$cli[0]["edificacao"] = "possuindo uma construção que ao todo mede {$cli[0]["tamanho-edificacao-imov"]}m² ({$cli[0]["tamanho-edificacao-imov-ext"]} quadrados)";
			} else {
				$cli[0]["edificacao"] = '';
			}
			$content->applyVars(array_merge($cli[0],array(
				"dia" => "{$data_atual[1]}",
				"mes" => "{$data_atual[2]}",
				"ano" => "{$data_atual[3]}"
			)));
			echo $content->getCode();
		}

		function page_recibo_cliente(UITemplate $content){
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
					$imoveis = $this->database()->query("pgtos", "status=pg and cliente =" . $this->url(2));
					$vendas = [];
					foreach($imoveis as $imovel){
						$cli = $this->database()->query("clientes", "id = {$imovel["cliente"]}");
						$cli = $cli[0];
						$ven = $this->database()->query("vendedores", "id = {$imovel["vendedor"]}");
						$ven = $ven[0];
						$imv = $this->database()->query("imoveis", "id = {$imovel["imovel"]}");
						$imv = $imv[0];
						$ven = array_merge($cli, $ven);
						$imovel["cliente"] = $ven["nome"];
						$imovel["cliente-id"] = $ven["id"];
						$imovel["imovel"] = $imovel["id"];
						$vendas[] = array_merge($ven,$cli,$imv,$imovel);
					}
					// $this->dbg($vendas);
					$vendedores = $vendas;
					foreach($vendedores as $k => $vendedor){
						$nparc = clsTexto::valorPorExtenso($k+1, false, false);
						$vendedores[$k]["valor-float"] = (float)str_replace(",", ".", str_replace(".", "", str_replace("R$ ", "", $vendedores[$k]["valor-parcela"])));
						// $this->dbg($comissao);
						$vendedores[$k]["comissao"] = "{$vendedores[$k]["valor-parcela"]}";
						$vendedores[$k]["imovel-local"] = "<a target=_blank href='/imovel/editar/{$vendedores[$k]["imovel"]}/' target=_blank>{$vendedores[$k]["rua"]}, {$vendedores[$k]["numero"]} {$vendedores[$k]["complemento"]} {$vendedores[$k]["bairro"]}</a><br><b>Produto:</b>&nbsp;{$vendedores[$k]["produto"]}";
						$vendedores[$k]["cliente"] = "<a target=_blank href='/vendedor/editar/{$vendedores[$k]["cliente-id"]}/' target=_blank>{$vendedores[$k]["cliente"]}</a>";
						$vendedores[$k]["acao"] = "<input type=checkbox data-imovel-id='{$vendedores[$k]["imovel"]}' data-parc='{$nparc}' data-nome='{$vendedores[$k]["nome"]}' data-valor='{$vendedores[$k]["valor-float"]}' data-serv='{$vendedores[$k]["produto"]}' data-mtd='{$vendedores[$k]["mtd-pgto"]}' data-forma='{$vendedores[$k]["forma-pgto"]}' class='incluir_dado' />";
						$vendedores[$k]["valor"] = "Parcela {$nparc}, no valor de {$vendedores[$k]["valor-parcela"]}";
					}
					$ven = $this->database()->query("vendedores", "id = {$this->url(2)}");
					$keyword         = "vendedor";
					$btnTxt          = "Vendedor";
					$db              = $vendedores;
					$titulos         = "Vendedor,Imovel,Parcela,<label class='d-block'><input data-skin=white data-toggle=m-tooltip data-placement=top title='' data-original-title='Selecionar Todos' type=checkbox class='incluir_todos float-left d-block' />&nbsp;Incluir no Recibo</label>";
					$dados           = "cliente,imovel-local,valor,acao";
					$keyid           = "id";
					$titulo          = "Recibo dos Clientes";

					exit($this->_tablepage($content,$keyword,$titulos,$dados,$keyid,$titulo,$db,$btnTxt,"not",false,"cliente/exportar","",["nome"=>$ven[0]["nome"], "ret" => ($this->url(3) == "vendedor" ? "vendedor":"recibo_cliente"), "data" => date("Y-m-d")])->getCode());
				break;

				case "pagamentos":
					$fin = $this->page_pagamentos_data(true);

					$why = $this->url(2);

					$clientes = [];

					foreach($fin["valores"] as $dat){
						$cliente = $dat[0];
						$pendentes = [];
						$emdia = [];
						if($why == -1 || (string)$cliente["id"] == (string)$why){
							// $this->dbg($dat[0]);
							foreach($dat[2] as $imovel){
								$date = new DateTime($cliente["data"]);
								$vzs = (int)preg_replace("/[^0-9]/", "", $imovel["forma-pgto"]);
								// $this->dbg($vzs);
								for($vz = 0; $vz < $vzs; $vz++){
									// $this->dbg($date->format("d/m/Y"));
									if(strtotime($d1=$date->format("Y-m-d")) < strtotime($d2=date("Y-m-d"))||1){
										if(count($pgto=$this->database()->query("pgtos", "cliente = {$cliente["id"]} and imovel = {$imovel["id"]} and data = " . $date->format("d/m/Y"))) > 0){
											if($pgto[0]["status"] === "not" && isset($dat[2][0]["pgtos"][$date->format("d/m/Y")]) && $dat[2][0]["pgtos"][$date->format("d/m/Y")] != "pg"){
												$pendentes[] = [$date->format("d/m/Y"), $pgto[0]["imovel"], $this->diff_dates($d2,$d1),$vz+1];
											} else {
												$emdia[] = [$date->format("d/m/Y"), $pgto[0]["imovel"], $this->diff_dates($d2,$d1),$vz+1];
											}
										} elseif(!(isset($dat[2][0]["pgtos"][$date->format("d/m/Y")]) && $dat[2][0]["pgtos"][$date->format("d/m/Y")] == "pg")) {
											$pendentes[] = [$date->format("d/m/Y"), $imovel["id"], $this->diff_dates($d2,$d1),$vz+1];
										} else {
											$emdia[] = [$date->format("d/m/Y"), $imovel["id"], $this->diff_dates($d2,$d1),$vz+1];
										}
									}
									$date->modify('+1 Month');
								}
							}
						}

						// $parcs_aplicadas = (count($pendentes)+count($emdia));
						//
						// while ($parcs_aplicadas--) {
						// 	$date = new DateTime($cliente["data"]);
						// 	$emdia[] = [$date->format("d/m/Y"), $imovel["id"], $this->diff_dates($d2,$d1),$vz+1];
						// }

						$cliente["pendentes"] = $pendentes;
						$cliente["emdia"] = $emdia;
						$clientes[] = $cliente;
					}

					$cliente = $this->database()->query($clientes, "id = {$why}");

					if(isset($cliente[0])){
						$cliente = $cliente[0];

						$tabela = [["Cliente","Imovel","Produto","Forma de Pgto","Valor da Parcela","Data","Status"]];

						foreach($cliente["emdia"] as $pendente){
							$imov = $this->database()->query("imoveis", "id = {$pendente[1]}");
							$tabela[] = [
								$cliente["nome"],
								"{$imov[0]["rua"]} {$imov[0]["numero"]}, {$imov[0]["bairro"]}", "{$imov[0]["produto"]}",
								"{$imov[0]["mtd-pgto"]}",
								"{$imov[0]["produto"]}",
								"{$imov[0]["valor-parcela-{$pendente[3]}"]}",
								$pendente[0],
								"Paga"
							];
						}

						foreach($cliente["pendentes"] as $pendente){
							$imov = $this->database()->query("imoveis", "id = {$pendente[1]}");
							$tabela[] = [
								$cliente["nome"],
								"{$imov[0]["rua"]} {$imov[0]["numero"]}, {$imov[0]["bairro"]}", "{$imov[0]["produto"]}",
								"{$imov[0]["mtd-pgto"]}",
								"{$imov[0]["produto"]}",
								"{$imov[0]["valor-parcela-{$pendente[3]}"]}",
								$pendente[0],
								"Pendente"
							];
						}

						$excel = parent::control("util/excel");
						$excel->SetDocumentTitle("relatorio-" . substr(sha1(date("dmYHis")),0,10));
						$excel->Instance()->getActiveSheet()->fromArray($tabela);
						$excel->Instance()->getActiveSheet()->getColumnDimension('A')->setWidth(60);
						$excel->Instance()->getActiveSheet()->getColumnDimension('B')->setWidth(70);
						$excel->Instance()->getActiveSheet()->getColumnDimension('C')->setWidth(30);
						$excel->Instance()->getActiveSheet()->getColumnDimension('D')->setWidth(30);
						$excel->Instance()->getActiveSheet()->getColumnDimension('E')->setWidth(30);
						$excel->Instance()->getActiveSheet()->getColumnDimension('F')->setWidth(30);
						$excel->Instance()->getActiveSheet()->getColumnDimension('G')->setWidth(30);
						$lastrow = $excel->Instance()->getActiveSheet()->getHighestRow();

						$excel->Instance()->getActiveSheet()->getStyle('A1:A'.$lastrow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
						$excel->Instance()->getActiveSheet()->getStyle('B1:B'.$lastrow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
						$excel->Instance()->getActiveSheet()->getStyle('C1:C'.$lastrow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
						$excel->Instance()->getActiveSheet()->getStyle('D1:D'.$lastrow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
						$excel->Instance()->getActiveSheet()->getStyle('E1:E'.$lastrow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
						$excel->Instance()->getActiveSheet()->getStyle('F1:F'.$lastrow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
						$excel->Instance()->getActiveSheet()->getStyle('G1:G'.$lastrow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

						exit($excel->Download());
					}

				break;

				case "produtos":
					$fin = $this->page_pagamentos_data(true);

					$why = $this->url(2);

					$clientes = [];

					foreach($fin["valores"] as $dat){
						$cliente = $dat[0];
						$pendentes = [];
						$emdia = [];
						if($why == -1 || (string)$cliente["id"] == (string)$why){
							// $this->dbg($dat[0]);
							foreach($dat[2] as $imovel){
								$date = new DateTime($cliente["data"]);
								$vzs = (int)preg_replace("/[^0-9]/", "", $imovel["forma-pgto"]);
								// $this->dbg($vzs);
								for($vz = 0; $vz < $vzs; $vz++){
									// $this->dbg($date->format("d/m/Y"));
									if(strtotime($d1=$date->format("Y-m-d")) < strtotime($d2=date("Y-m-d"))||1){
										if(count($pgto=$this->database()->query("pgtos", "cliente = {$cliente["id"]} and imovel = {$imovel["id"]} and data = " . $date->format("d/m/Y"))) > 0){
											if($pgto[0]["status"] === "not" && isset($dat[2][0]["pgtos"][$date->format("d/m/Y")]) && $dat[2][0]["pgtos"][$date->format("d/m/Y")] != "pg"){
												$pendentes[] = [$date->format("d/m/Y"), $pgto[0]["imovel"], $this->diff_dates($d2,$d1),$vz+1];
											} else {
												$emdia[] = [$date->format("d/m/Y"), $pgto[0]["imovel"], $this->diff_dates($d2,$d1),$vz+1];
											}
										} elseif(!(isset($dat[2][0]["pgtos"][$date->format("d/m/Y")]) && $dat[2][0]["pgtos"][$date->format("d/m/Y")] == "pg")) {
											$pendentes[] = [$date->format("d/m/Y"), $imovel["id"], $this->diff_dates($d2,$d1),$vz+1];
										} else {
											$emdia[] = [$date->format("d/m/Y"), $imovel["id"], $this->diff_dates($d2,$d1),$vz+1];
										}
									}
									$date->modify('+1 Month');
								}
							}
						}

						// $parcs_aplicadas = (count($pendentes)+count($emdia));
						//
						// while ($parcs_aplicadas--) {
						// 	$date = new DateTime($cliente["data"]);
						// 	$emdia[] = [$date->format("d/m/Y"), $imovel["id"], $this->diff_dates($d2,$d1),$vz+1];
						// }

						$cliente["pendentes"] = $pendentes;
						$cliente["emdia"] = $emdia;
						$clientes[] = $cliente;
					}

					$cliente = $this->database()->query($clientes, "id = {$why}");

					if(isset($cliente[0])){
						$cliente = $cliente[0];

						$tabela = [["Cliente","Imovel","Produto Adquirido","Data de Aquisição","Forma de Pagamento"]];

						foreach($this->database()->query("imoveis", "cliente = {$cliente["id"]}") as $imov){
							$tabela[] = [
								$cliente["nome"],
								"{$imov["rua"]} {$imov["numero"]}, {$imov["bairro"]}", "{$imov["produto"]}",
								"{$imov["data-aquisicao"]}",
								"{$imov["mtd-pgto"]}"
							];
						}

						$excel = parent::control("util/excel");
						$excel->SetDocumentTitle("relatorio-" . substr(sha1(date("dmYHis")),0,10));
						$excel->Instance()->getActiveSheet()->fromArray($tabela);
						$excel->Instance()->getActiveSheet()->getColumnDimension('A')->setWidth(60);
						$excel->Instance()->getActiveSheet()->getColumnDimension('B')->setWidth(70);
						$excel->Instance()->getActiveSheet()->getColumnDimension('C')->setWidth(30);
						$excel->Instance()->getActiveSheet()->getColumnDimension('D')->setWidth(30);
						$excel->Instance()->getActiveSheet()->getColumnDimension('E')->setWidth(30);
						$excel->Instance()->getActiveSheet()->getColumnDimension('F')->setWidth(30);
						$excel->Instance()->getActiveSheet()->getColumnDimension('G')->setWidth(30);
						$lastrow = $excel->Instance()->getActiveSheet()->getHighestRow();

						$excel->Instance()->getActiveSheet()->getStyle('A1:A'.$lastrow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
						$excel->Instance()->getActiveSheet()->getStyle('B1:B'.$lastrow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
						$excel->Instance()->getActiveSheet()->getStyle('C1:C'.$lastrow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
						$excel->Instance()->getActiveSheet()->getStyle('D1:D'.$lastrow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
						$excel->Instance()->getActiveSheet()->getStyle('E1:E'.$lastrow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
						$excel->Instance()->getActiveSheet()->getStyle('F1:F'.$lastrow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
						$excel->Instance()->getActiveSheet()->getStyle('G1:G'.$lastrow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

						exit($excel->Download());
					}

				break;

				default:
					$vendedores = $this->get_clientes_status();

					// $this->dbg($vendedores);

					foreach($vendedores as $k => $vendedor){
						$parc = count($vendedores[$k]["pendentes"]);
						$vendedores[$k]["pendentes"] = "{$parc} parcela(s)";
						$vendedores[$k]["acao"] = "<a class='btn m-btn btn-outline-dark' href='/recibo_cliente/exportar/{$vendedores[$k]["id"]}/'><i class='la la-list'></i>&nbsp;Acessar</a>";
						$vendedores[$k]["outros"] = "<a class='btn m-btn btn-success m-2 my-4' href='/recibo_cliente/pagamentos/{$vendedores[$k]["id"]}/' data-skin='white' data-toggle='m-tooltip' data-placement='top' title='' data-original-title='Relatório de Pagamentos'><i style='font-size: 2rem;' class='la la-money px-0 py-2'></i></a><a class='btn m-btn btn-info m-2 my-4' href='/recibo_cliente/produtos/{$vendedores[$k]["id"]}/' data-skin='white' data-toggle='m-tooltip' data-placement='top' title='' data-original-title='Relatório de Produtos Adquiridos'><i style='font-size: 2rem;' class='la la-star px-0 py-2'></i></a>";
						$vendedores[$k]["nome"] = "<a target=_blank href='/cliente/editar/{$vendedores[$k]["id"]}/' target=_blank>{$vendedores[$k]["nome"]}</a>";
					}
					$btnTxt          = "Cliente";
					$keyword         = "cliente";
					$db              = $vendedores;
					$titulos         = "Nome,E-mail,parcelas pendentes,Recibo,Mais Opções&nbsp;<i class='  la-1x  la la-question-circle-o' data-skin='white' data-toggle='m-tooltip' data-placement='top' title='' data-original-title='Passe o mouse na opção/botão para saber seu título.'></i>";
					$dados           = "nome,email,pendentes,acao,outros";
					$keyid           = "id";
					$titulo          = "Recibo dos Clientes";

					exit($this->_tablepage($content,$keyword,$titulos,$dados,$keyid,$titulo,$db,$btnTxt,"not",false,"vendedor/texp")->getCode());
				break;
			}
		}

		function page_recibo_cliente_imprimir(UITemplate $content){
			// $this->dbg($_POST);
			$content->uiTemplate("recibo-cliente");
			$data_atual = $this->data_atual($_POST['data']);
			$content->applyVars(array(
				"total" => "R$ " . number_format($_POST['valor'], 2, ",", "."),
				"total-extenso" => clsTexto::valorPorExtenso("R$ " . number_format($_POST['valor'], 2, ",", "."), true, false),
				"n-vendas" => $_POST['parc'],
				"n-vendas-extenso" => clsTexto::valorPorExtenso($_POST['parc'], false, true),
				"pgto" => $_POST['mtd'],
				"vzs" => clsTexto::valorPorExtenso($_POST['forma'], false, true),
				"nome" => $_POST["nome"],
				"dia" => "{$data_atual[1]}",
				"mes" => "{$data_atual[2]}",
				"ano" => "{$data_atual[3]}"
			));
			echo $content->getCode();
		}

		function page_cliente(UITemplate $content){
            $content->minify = true;

			$vars = [];
			$vars["myid"]    = "";
			$vars["data"]    = "";
			$vars["modo"]    = "";
			$vars["titulo"]  = "";
			$vars["cliente"] = "[]";
			$vars["conjuge"] = "[]";
			$vars["imoveis"] = "[]";
			$vars["texto_botao"] = "";
			$vars["vendedores"] = $this->transform_to_option($this->get_vendedores());
			$vars["form-vendedor"] = $content->loadModel("vendedor/formulario", []);

			switch($this->url(1)){
				case "novo":
					$vars["myid"] = $this->database()->newID("clientes");
					$vars["data"] = date("Y-m-d");
					$vars["modo"] = "criar";
					$vars["titulo"] = "Adicionar Novo Cliente";
					$vars["texto_botao"] = "Salvar";

					$content = $this->simple_loader($content, "cliente/formulario", $vars);

					echo $content->getCode();
				break;
				case "editar":
					$vars["myid"] = $this->url(2);
					$vars["data"] = date("Y-m-d");
					$vars["modo"] = "mod";
					$vars["titulo"] = "Modificar Este Cliente";
					$vars["texto_botao"] = "Modificar";

					$id = $vars["myid"];
					$vars["cliente"] = json_encode($this->database()->query("clientes", "id = {$id}"));
					$vars["conjuge"] = json_encode($this->database()->query("conjuges", "id = {$id}"));
					$vars["imoveis"] = ($this->database()->query("imoveis",  "cliente = {$id}"));

					if(!isset($vars["imoveis"][0]["valor-venda-real"])){
						$vars["imoveis"][0]["valor-venda-real"] = "R$ 2.500,00";
						$vars["imoveis"][0]["valor-venda-desconto"] = "R$ 0,00";
					}

					$vars["imoveis"] = json_encode($vars["imoveis"]);

					$content = $this->simple_loader($content, "cliente/formulario", $vars);

					echo $content->getCode();
				break;
				default:
					$clientes = $this->get_clientes_status();

					$btnTxt          = "Cliente";
					$keyword         = "cliente";
					$db              = $clientes;
					$titulos         = "Nome,E-mail,Telefone,Documento,Estado Civil";
					$dados           = "nome,email,tel1,doc,estado-civil";
					$keyid           = "id";
					$titulo          = "Gerir Clientes Cadastrados";

					exit($this->_tablepage($content,$keyword,$titulos,$dados,$keyid,$titulo,$db,$btnTxt)->getCode());
				break;
			}
        }

		function get_clientes_status($why=-1){
			$fin = $this->page_pagamentos_data(true);

			$clientes = [];

			foreach($fin["valores"] as $dat){
				$cliente = $dat[0];
				$pendentes = [];
				if($why == -1 || (string)$cliente["id"] == (string)$why){
				// $this->dbg($dat[0]);
					foreach($dat[2] as $imovel){
						$date = new DateTime($cliente["data"]);
						$vzs = (int)preg_replace("/[^0-9]/", "", isset($imovel["forma-pgto"]) ? $imovel["forma-pgto"]:"0");
						// $this->dbg($vzs);
						for($vz = 0; $vz < $vzs; $vz++){
							// $this->dbg($date->format("d/m/Y"));
							if(strtotime($d1=$date->format("Y-m-d")) < strtotime($d2=date("Y-m-d"))){
								if(count($pgto=$this->database()->query("pgtos", "cliente = {$cliente["id"]} and imovel = {$imovel["id"]} and data = " . $date->format("d/m/Y"))) > 0){
									if($pgto[0]["status"] === "not" && isset($dat[2][0]["pgtos"][$date->format("d/m/Y")]) && $dat[2][0]["pgtos"][$date->format("d/m/Y")] != "pg"){
										$pendentes[] = [$date->format("d/m/Y"), $pgto[0]["imovel"], $this->diff_dates($d2,$d1),$vz+1];
									}
								} elseif(isset($dat[2][0]["pgtos"][$date->format("d/m/Y")]) && $dat[2][0]["pgtos"][$date->format("d/m/Y")] != "pg") {
									$pendentes[] = [$date->format("d/m/Y"), $imovel["id"], $this->diff_dates($d2,$d1),$vz+1];
								}
							}


							$date->modify('+1 month');
						}
					}
				}
				$cliente["pendentes"] = $pendentes;
				$clientes[] = $cliente;
			}

			return $clientes;
		}

		function page_ajax_cliente(){
			$id      = isset($_POST["data"][0]) ? $_POST["data"][0]:"";
			$cliente = isset($_POST["data"][1]) ? $_POST["data"][1]:"";
			$conjuge = isset($_POST["data"][2]) ? $_POST["data"][2]:"";
			$imoveis = isset($_POST["data"][3]) ? $_POST["data"][3]:"";
			$modo    = isset($_POST["data"][4]) ? $_POST["data"][4]:"";

			if($modo == "criar"){
				if($conjuge !== "not"){
					$conjuge["id"] = $id;
					$this->database()->push("conjuges", array($conjuge));
				}

				$cliente["id"] = $id;

				$this->database()->push("clientes", array($cliente));

				for($i = 0; $i < count($imoveis); $i++){
					$imoveis[$i]["id"] = $this->database()->newID("imoveis");
					$imoveis[$i]["cliente"] = $id;
				}

				$this->database()->push("imoveis", $imoveis);
			} elseif($modo == "mod"){
				if($conjuge !== "not"){
					$conjuge["id"] = $id;
					$this->database()->setWhere("conjuges", "id = {$id}", $conjuge);
				}

				$cliente["id"] = $id;

				$this->database()->setWhere("clientes", "id = {$id}", $cliente);
				for($i = 0; $i < count($imoveis); $i++){
					// $imoveis[$i]["id"] = $this->database()->newID("imoveis");
					// $imoveis[$i]["cliente"] = $id;
					$this->database()->setWhere("imoveis", "id = {$imoveis[$i]["id"]}", $imoveis[$i]);
					// $this->json($imoveis[$i]);
				}
			} elseif($cliente == "erase"){
				$this->database()->deleteWhere("conjuges", "id = {$id}");
				$this->database()->deleteWhere("clientes", "id = {$id}");
				$this->database()->deleteWhere("imoveis", "cliente = {$id}");
			}
			$this->json(true);
		}
	}

?>
