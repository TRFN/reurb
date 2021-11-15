<?php

	trait pagamentos {
		function page_pagamentos(UITemplate $content, $modelo = "financeiro/pagamentos"){
			$content->minify = true;

			$vars = ["nav-meses"=>"", "content-meses"=>"", "ano" => date("Y"), "mes" => date("m"), "dia" => date("d")];

			$vars["thead-inad"] = "<th style='text-transform: uppercase; width: 80px;'>Data</th><th style='text-transform: uppercase; width: 150px;'>Nome</th><th style='text-transform: uppercase; width: 100px;'>Email</th><th style='text-transform: uppercase; width: 60px;'>Parcela</th><th style='text-transform: uppercase; width: 250px;'>Imovel</th><th style='text-transform: uppercase; width: 80px;'>Atraso</th><th style='text-transform: uppercase; width: 80px;'>Status</th>";

			$vars["tbody-inad"] = "";

			foreach($this->get_clientes_status() as $cli){
				foreach($cli["pendentes"] as $pendencia){
					$imovel = $this->database()->query("imoveis", "id = {$pendencia[1]}");
					if(count($imovel)){
						$imovel = $imovel[0];
						$cor = (140+$pendencia[2]);
						$pendencia["3-ext"] = clsTexto::valorPorExtenso($pendencia[3], false, false);
						$vars["tbody-inad"] .= "<tr data-parc=\"{$pendencia[3]}\" style='background-color: hsl({$cor},60%,99%)'><td>{$pendencia[0]}</td><td><a style='color: #000!important;' target=_blank href='/cliente/editar/{$cli["id"]}/'>{$cli["nome"]}</a></td><td>{$cli["email"]}</td><td>{$pendencia[3]} ({$pendencia["3-ext"]})</td><td><a style='color: #000!important;' target=_blank href='/imovel/editar/{$imovel["id"]}/'>{$imovel["rua"]} N {$imovel["numero"]}<br>{$imovel["bairro"]}<br>{$imovel["cidade"]} / {$imovel["estado"]}</a></td><td>{$pendencia[2]} dia(s)</td><td class=status></td></tr>";
					}
				}
			}

			$meses = [
				["jan","Janeiro",0],
				["fev","Fevereiro",0],
				["mar","Março",0],
				["abr","Abril",0],
				["mai","Maio",0],
				["jun","Junho",0],
				["jul","Julho",0],
				["ago","Agosto",0],
				["set","Setembro",0],
				["out","Outubro",0],
				["nov","Novembro",0],
				["dez","Dezembro",0]
			];

			foreach($meses as $imes=>$mes){
				$imes = $imes + 1;

				$act = (($mes[2] == 1 || (int)$vars["mes"] == (int)$imes) ? [" active", ' aria-selected="true"'," show active"]:["","",""]);

				$vars["nav-meses"] .= '
				  <a class="nav-item nav-link'.$act[0].'" id="link-'.$mes[0].'" data-toggle="tab" href="#'.$mes[0].'" role="tab" aria-controls="'.$mes[0].'" '.$act[1].'>'.$mes[1].'</a>
				';

				$vars["content-meses"] .= '<div class="tab-pane mes-'.$imes.' fade'.$act[2].'" id="'.$mes[0].'" role="tabpanel" aria-labelledby="'.$mes[0].'">
					<h2>Mês de '.$mes[1].' de <span class="ano">'.$vars["ano"].'</span></h2>
					<br><br>
					<div class="table-responsive">
						<h4>Pagamentos deste mês</h4>
							<table id="table-'.$mes[0].'" class="table table-bordered table-hover table-striped">
					        <thead>
					            <tr>
					                <th class="text-center text-uppercase" style="width: 50px!important;">Data</th>
					                <th class="text-center text-uppercase" style="width: 120px!important;">Nome</th>
					                <th class="text-center text-uppercase" style="width: 200px!important;">Detalhes</th>
					                <th class="text-center text-uppercase" style="width: 200px!important;">Pagamento</th>
					                <th class="text-center text-uppercase" style="width: 80px!important;">Status</th>
					            </tr>
					        </thead>
		        			<tbody>
		        			</tbody>
	    				</table>
					</div>
					<br><br>
				</div>';
			}

			$vars["data"] = date("Y-m-d");

            $content = $this->simple_loader($content, $modelo, $vars);

            echo $content->getCode();
        }

		function page_inadimplentes(UITemplate $content){
			$this->page_pagamentos($content, "financeiro/inadimplentes");
        }

		function page_boletos(UITemplate $content){
			$this->page_pagamentos($content, "financeiro/boletos");
		}

		function page_pagamentos_data($fn=false){
			$valores = [];

			$clientes = [];

			foreach($this->get_clientes() as $cliente){
				$conjuge = $this->database()->query("conjuges", "id = {$cliente["id"]}");
				$imoveis = $this->database()->query("imoveis",  "cliente = {$cliente["id"]}");
				$exportacao = $this->database()->query("expbanco", "cliente = {$cliente["id"]}");

				foreach(array_keys($imoveis) as $index){
					$imov_id = $imoveis[$index]["id"];
					// $this->dbg($this->database()->query("pgtos", "cliente = {$cliente["id"]} and imovel = {$imov_id}"));
					$imoveis[$index]["pgtos"] = [];
					foreach($this->database()->query("pgtos", "cliente = {$cliente["id"]} and imovel = {$imov_id}") as $pgto){
						$imoveis[$index]["pgtos"][$pgto["data"]] = $pgto["status"];
					}
					$imoveis[$index]["vendedor"] = $this->database()->query("vendedores", "id = {$imoveis[$index]["vendedor"]}");
					$imoveis[$index]["vendedor"] = isset($imoveis[$index]["vendedor"][0])?$imoveis[$index]["vendedor"][0]:["id"=>"","nome"=>""];

					// foreach(array_keys($imoveis[$index]["pgtos"]) as $pgt){
					// 	$imoveis[$index]["pgtos"][$pgt] =
					// }
				}
				$imov = 0;
				foreach(array_keys($exportacao) as $index){
					$imov_id = $imoveis[$imov]["id"];

					// $this->dbg($this->database()->query("pgtos", "cliente = {$cliente["id"]} and imovel = {$imov_id}"));

					if(!isset($imoveis[$imov]["pgtos"])){
						$imoveis[$imov]["pgtos"] = [];
					}
					$parc = 1;
					$protect_while = 0;
					while($exportacao[$index]["dados"]["valor"] > 0 && $protect_while < 1000){
						$protect_while++;
						if(isset($imoveis[$imov]["valor-parcela-{$parc}"]) && !empty($imoveis[$imov]["valor-parcela-{$parc}"])){
							$exportacao[$index]["dados"]["valor"] -= (float)str_replace(",", ".", str_replace(".", "", str_replace("R$ ", "", $imoveis[$imov]["valor-parcela-{$parc}"])));

							$mes = $parc - 1;
							$mes = date("d/", strtotime($imoveis[$imov]["data"])) . date("m/Y", strtotime(("{$imoveis[$imov]["data"]} +{$mes} Month")));

							// $this->dbg($mes);

							if(!isset($imoveis[$imov]["pgtos"][$mes])){
								$imoveis[$imov]["pgtos"][$mes] = "pg";
							}

							$parc++;
						}
					}

					// foreach(array_keys($imoveis[$index]["pgtos"]) as $pgt){
					// 	$imoveis[$index]["pgtos"][$pgt] =
					// }

					// $this->dbg($imoveis[$imov]);

					$imov++;
				}
				$clientes[] = [$cliente, $conjuge, $imoveis];
			}

			$result = (["valores" => $clientes, "checksum"=>sha1(json_encode($clientes))]);
			return($fn===true)?$result:$this->json($result);
		}

		function page_change_status(){
			if(count($this->database()->query("pgtos", "cliente = {$_POST["data"][0]} and imovel = {$_POST["data"][2]} and data = {$_POST["data"][3]}")) > 0){
				$this->database()->setWhere("pgtos", "cliente = {$_POST["data"][0]} and imovel = {$_POST["data"][2]} and data = {$_POST["data"][3]}", ["status" => ($_POST["data"][4] == 1?"pg":'not')]);
			} else {
				$this->database()->push("pgtos", array(
					array(
						"cliente" => $_POST["data"][0],
						"imovel" => $_POST["data"][2],
						"vendedor" => $_POST["data"][1],
						"data" => $_POST["data"][3],
						"status" => ($_POST["data"][4] == 1?"pg":'not')
					)
				));
			}
		}

		function page_aplicar_automatico(){
			$data = json_decode($_POST["dado"], true);
			$this->database()->push("expbanco", array($data), "log_remove");
		}

		function page_get_automatico(){
			$this->json($this->database()->get("expbanco"));
		}
	}

?>
