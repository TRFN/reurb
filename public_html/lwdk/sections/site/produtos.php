<?php
	trait site_produtos {
		function loja_produtos($queryby="@ID > -1",$basics="ativo = true"){
			return(parent::database()->query("produtos", "{$basics} && {$queryby}"));
		}

		function page_json_produtos(){
			$retorno = [];
			foreach($this->loja_produtos(isset($_POST["prod"])?"id={$_POST["prod"]}":"@ID > -1") as $p){
				$fav_state = isset($this->sessao()->favs) ? $this->sessao()->favs:array();
				if(in_array($p["id"], $fav_state)){
					$key = array_search($p["id"], $fav_state);
					if($key !== false){
					    $fav_state = true;
					}
				}

				$fav_state = $fav_state === true ? "fas":"far";

				$retorno[] = array_merge($p, array(
					"id" => $p["id"],
					"nome" => $p["nome"],
					"cat" => (($cat=$this->get_cat($p["categoria"])) !== false ? (
						($subcat=$this->get_subcat($p["subcategoria"])) !== false
							? "{$cat} / {$subcat["txt"]}"
							: "{$cat}"
					) : ""),
					"img" => ($img=(isset($p["imagens"]) && isset($p["imagens"][0]))) ? $p["imagens"][0]["url"] : "img/products/product-grey-1.jpg",
					"imgt" => $img ? $p["imagens"][0]["legend"] : "",
					"link" => "/produtos/{$p["id"]}-" . $this->slug("{$cat} {$subcat["txt"]} {$p["nome"]}") . "/",
					"old_price" => ($promo=("{$p["valor-a-vista"]}"=="R$ 0,00")) ? "" : $p["valor"],
					"price" => $promo ? $p["valor"] : $p["valor-a-vista"],
					"fav" => $fav_state,
					"disponivel" => (int)$p["quantidade_estoque"] > 0
				));
			}
			$this->json($retorno);
		}

		function get_cat($id=-1){
			$cats = parent::database()->query("produtos", "name = categorias",array("content"));
			$id = (int)$id;
			return (count($cats) > 0 ? ($id === -1 ? $cats[0]["content"] : (isset($cats[0]["content"][$id]) ? $cats[0]["content"][$id] : false)) : false);
		}

		function get_subcat($id=-1){
			$cats = parent::database()->query("produtos", "name = subcategorias",array("content"));
			$id = (int)$id;
			return (count($cats) > 0 ? ($id === -1 ? $cats[0]["content"] : (isset($cats[0]["content"][$id]) ? $cats[0]["content"][$id] : false)) : false);
		}

		function modelo_minhatura_produtos(UITemplate $content, $queryby="@ID > -1", $limit = 10, $style1 = "product mb-0", $init="", $end=""){
			$retorno = "";
			$obj = (is_array($queryby) ? $queryby : $this->loja_produtos($queryby));
			// $this->dbg($obj);
			foreach($obj as $p){
				$fav_state = isset($this->sessao()->favs) ? $this->sessao()->favs:array();
				if(in_array($p["id"], $fav_state)){
					$key = array_search($p["id"], $fav_state);
					if($key !== false){
						$fav_state = true;
					}
				}

				$fav_state = $fav_state === true ? "fas":"far";
				if($limit < 1){break;}
				// $this->dbg($p);
				$retorno .= $content->loadModel("site/produto-minhatura", array(
					"id" => $p["id"],
					"nome" => $p["nome"],
					"cat" => (($cat=$this->get_cat($p["categoria"])) !== false ? (
						($subcat=$this->get_subcat($p["subcategoria"])) !== false
							? "{$cat} / {$subcat["txt"]}"
							: "{$cat}"
					) : ""),
					"queryCat" => "{$p["categoria"]}/{$p["subcategoria"]}",
					"img" => ($img=(isset($p["imagens"]) && isset($p["imagens"][0]))) ? $p["imagens"][0]["url"] : "img/products/product-grey-1.jpg",
					"imgt" => $img ? $p["imagens"][0]["legend"] : "",
					"link" => "/produtos/{$p["id"]}-" . $this->slug("{$cat} {$subcat["txt"]} {$p["nome"]}") . "/",
					"old_price" => ($promo=("{$p["valor-a-vista"]}"=="R$ 0,00"||empty("{$p["valor-a-vista"]}"))) ? "" : $p["valor"],
					"price" => $promo ? $p["valor"] : $p["valor-a-vista"],
					"fav" => $fav_state,
					"style1" => $style1,
					"init" => $init,
					"end" => $end,
					"disponivel" => (int)$p["quantidade_estoque"] > 0 ? "addtocart-btn-wrapper":"d-none",
					"indisponivel" => (int)$p["quantidade_estoque"] < 1 ? "addtocart-btn-wrapper":"d-none"
				));
				$limit--;
			}
			return $retorno;
		}

		function page_facebook_list(){
			$fba = parent::control("connect/facebookApi");
			$produtos = [];

			foreach($this->loja_produtos("@ID > -1", "@ID > -1") as $p){
				$p["descricao-curta"] = ($ns=substr(($p["descricao-curta"]=strip_tags($p["descricao-curta"])),0,100)) != $p["descricao-curta"]
					? $ns . "..."
					: $p["descricao-curta"];

				$produtos[] = array_merge($p, array(
					"id" => $p["id"],
					"nome" => $p["nome"],
					"cat" => (($cat=$this->get_cat($p["categoria"])) !== false ? (
						($subcat=$this->get_subcat($p["subcategoria"])) !== false
							? "{$cat} / {$subcat["txt"]}"
							: "{$cat}"
					) : ""),
					"img" => ($img=(isset($p["imagens"]) && isset($p["imagens"][0]))) ? $p["imagens"][0]["url"] : "img/products/product-grey-1.jpg",
					"link" => "{$this->mydomain}/produtos/{$p["id"]}-" . $this->slug("{$cat} {$subcat["txt"]} {$p["nome"]}") . "/",
					"price" => str_replace(",",".",(preg_replace("/[^0-9\,]/","",("{$p["valor-a-vista"]}"=="R$ 0,00") ? $p["valor"] : $p["valor-a-vista"]))) . " BRL",
					"gpc" => array($cat,$p["nome"],$subcat["txt"]),
					"stock" => $p["quantidade_estoque"] > 0
						? "in stock"
						: "out of stock",
					"new" => "new"
				));
			}

			$fba->setData($produtos, array(
				"id" => "id",
				"title" => "nome",
				"description" => "descricao-curta",
				"availability" => "stock",
				"condition" => "new",
				"price" => "price",
				"link" => "link",
				"image_link" => "img",
				"brand" => "cat",
				"google_product_category" => "gpc"
			));

			$fba->render();
		}

		function page_produtos(UITemplate $content){
			$link = explode("-",$this->url(1));

			$error = !isset($link[0]) || preg_match("/[^0-9]/", $link[0]);

			if(!($error = $error || (count($query = $this->loja_produtos("id = {$link[0]}")) == 0))){
				$produto = $query[0];

				$p = $produto;

				$imgs = "";
				$imgs_thumbs = "";

				$fav_state = isset($this->sessao()->favs) ? $this->sessao()->favs:array();
				if(in_array($p["id"], $fav_state)){
					$key = array_search($p["id"], $fav_state);
					if($key !== false){
					    $fav_state = true;
					}
				}
				$fav_state = $fav_state === true ? "fas":"far";

				foreach($produto["imagens"] as $img){
					$imgs .=
						"<div>
							<img alt=\"{$img["legend"]}\" class=\"img-fluid\" src=\"{$img["url"]}\" />
						</div>";

					$imgs_thumbs .=
						"<div class=\"cur-pointer\">
							<img alt=\"{$img["legend"]}\" class=\"img-fluid\" src=\"{$img["url"]}\" />
						</div>";
				}

				$tags = implode("% or nome = %", explode(" ", $p["nome"]));

				$indicacoes = $this->modelo_minhatura_produtos($content, "categoria = {$p["categoria"]} or subcategoria = {$p["subcategoria"]} and nome = %{$tags}%", 30);

				if($indicacoes === ""){
					$indicacoes = $this->modelo_minhatura_produtos($content);
				}

				$dados = array_merge($p, array(
					"titulo" => $p["nome"],
					"cat" => (($cat=$this->get_cat($p["categoria"])) !== false ? (
						($subcat=$this->get_subcat($p["subcategoria"])) !== false
							? "{$cat} / {$subcat["txt"]}"
							: "{$cat}"
					) : ""),
					"imgs" => $imgs,
					"imgs_thumb" => $imgs_thumbs,
					"link" => "/produtos/{$p["id"]}-" . $this->slug("{$cat} {$subcat["txt"]} {$p["nome"]}") . "/",
					"old_price" => ($promo=("{$p["valor-a-vista"]}"=="R$ 0,00" || empty($p["valor-a-vista"])))
						? ""
						: $p["valor"],
					"price" => $promo ? $p["valor"]:$p["valor-a-vista"],
					"fav" => $fav_state,
					"indicacoes" => $indicacoes,
					"disponivel" => (int)$p["quantidade_estoque"] > 0 ? "":"d-none",
					"indisponivel" => (int)$p["quantidade_estoque"] < 1 ? "":"d-none",
					"virtualh" => (!isset($p["tipo"]) || $p["tipo"] == "fisico") ? "":"d-none",
					"virtuald" => (!isset($p["tipo"]) || $p["tipo"] == "fisico") ? "d-none":""

				));

				$dados["TITLE"] = "{$p["nome"]} - {$dados["cat"]}";

				exit($this->simple_loader($content, "site/detalhes", $dados)->getCode());
			}

			if($error){
				$this->notfound($content);
			}

		}

		function page_fechar_pedido(UITemplate $content){
			if($this->sessao()==false){
				header("Location: /entrar/");
			}
			if($this->post()){
				$this->pagarme()->passive = true;

				$post = $_POST;

				// $this->dbg($post);

				if($post["pagamento"] !== "-1"){
					$cartao = (int)$post["pagamento"];
					$this->pagarme()->set->client
						->name($this->sessao()->name)
						->document($this->sessao()->cards[$cartao]["c_cpf"])
						->phone(isset($this->sessao()->phone)?("+55" . preg_replace("/[^0-9]/","", $this->sessao()->phone)):"+5511111111111")
						->email($this->sessao()->email)
						->street($this->sessao()->cards[$cartao]["c_rua"])
						->street_number($this->sessao()->cards[$cartao]["c_numero"])
						->neighborhood($this->sessao()->cards[$cartao]["c_bairro"])
						->city($this->sessao()->cards[$cartao]["c_cidade"])
						->state($this->sessao()->cards[$cartao]["c_estado"])
						->zipcode(preg_replace("/[^0-9]/","",$this->sessao()->cards[$cartao]["c_cep"]))
						->parent->method(1);


						# Dados do Cartao para Pagamento

						$this->pagarme()->set->card
							->name($this->sessao()->cards[$cartao]["c_name"])
							->number(preg_replace("/[^0-9]/","",$this->sessao()->cards[$cartao]["c_number"]))
							->cvv(preg_replace("/[^0-9]/","",$this->sessao()->cards[$cartao]["c_cvv"]))
							->expires(preg_replace("/[^0-9]/","",$this->sessao()->cards[$cartao]["c_expires"]));

						$post["card"] = $this->sessao()->cards[$cartao];
						$post["card_id"] = $cartao;
				} else {
					$end = $post["endereco"] === "-1" ? 0 : (int)$post["endereco"];

					// $this->dbg([$this->sessao()->enderecos[$end],!isset($this->sessao()->enderecos),!is_array($this->sessao()->enderecos),!isset($this->sessao()->enderecos[$end]),strlen($this->sessao()->enderecos[$end]["cep"]) < 8]);

					if(!isset($this->sessao()->enderecos) || !is_array($this->sessao()->enderecos) || !isset($this->sessao()->enderecos[$end]) || strlen($this->sessao()->enderecos[$end]["cep"]) < 8){
						header("Location: /fechar_pedido/?houveumerro=2");
					}

					$this->pagarme()->set->client
						->name($this->sessao()->name)
						->document($this->sessao()->doc)
						->phone(isset($this->sessao()->phone)?("+55" . preg_replace("/[^0-9]/","", $this->sessao()->phone)):"+5511111111111")
						->email($this->sessao()->email)
						->street($this->sessao()->enderecos[$end]["rua"])
						->street_number($this->sessao()->enderecos[$end]["numero"])
						->neighborhood($this->sessao()->enderecos[$end]["bairro"])
						->city($this->sessao()->enderecos[$end]["cidade"])
						->state($this->sessao()->enderecos[$end]["estado"])
						->zipcode(preg_replace("/[^0-9]/","", $this->sessao()->enderecos[$end]["cep"]))
						->parent->method(0);

					$post["card"] = ["c_name"=>"","c_number"=>"","c_cvv"=>"","c_expires"=>""];
					$post["card_id"] = "-1";
				}

				$this->pagarme()->set->shippingFee((float)str_replace(",",".",preg_replace("/[^0-9,]/","",str_replace("R$ ", "", $post["envio"]))));

				# Produtos para Pagamento
				$UserData = parent::control("interactive/userdata");
				$UserData->Timeout("+4 Years");

				$produtos = $UserData->Get("carrinho");

				// $this->dbg($this->pagarme());

				$post["produtos"] = [];

				$cupons = $this->getUData("cupons", []);

				foreach($produtos as $id=>$qtd){
					$prod = $this->loja_produtos("id = {$id}");

					if(isset($prod[0])){
						$prod = $prod[0];

						$valor = !($promo=("{$prod["valor-a-vista"]}"=="R$ 0,00"||empty("{$prod["valor-a-vista"]}"))) ? "{$prod["valor-a-vista"]}" : "{$prod["valor"]}";

						$valor = (float)str_replace(",",".",preg_replace("/[^0-9,]/","",str_replace("R$ ", "", $valor)));

						$prd = array(
							"name" => $prod["nome"],
							"price" => $valor,
							"quantity" => (int)$qtd,
							"virtual"  => $prod["tipo"] == "virtual"
						);

						$prd["id"] = $prod["id"];

						// $this->dbg($prd);

						$this->pagarme()->set->product->add($prd);

						$post["produtos"][] = $prd;
					} elseif(!in_array($id, $cupons)){
						$query = parent::database()->query("produtos", "name = cupons_desc",array("content"));

			            if(count($query) < 1){
			                $cats = array();
			            } else {
			                $cats = $query[0]["content"];
			            }

						foreach($cats as $k=>$v){
							$cats[$k] = strtolower($v);
						}

						// $this->dbg(array_search(strtolower($id), $cats) !== false);

						if(array_search(strtolower($id), $cats) !== false){
							$valor = $qtd;

							$valor = (float)str_replace(",",".",preg_replace("/[^0-9,]/","",str_replace("R$ ", "", $valor)));

							$prd = array(
								"name" => "Desconto de {$qtd}.",
								"price" => -$valor,
								"quantity" => 1,
								"virtual"  => false
							);

							$prd["id"] = "desconto-" . md5(uniqid());

							$cupons[] = $id;

							$this->pagarme()->set->product->add($prd);

							$post["produtos"][] = $prd;
						}
					}
				}

				if($this->pagarme()->price >= 100){
					$pagar = $this->pagarme()->pay();
				} else {
					$pagar = array("id" => sha1(uniqid()), "success" => true, "status" => "paid", "pago" => 0, "criado_em" => date("d/m/Y \a\s H:i"), "others" => ["amount" => 0]);

					$post = array_merge($post, $pagar);
				}

				$post["id"] = $pagar["id"];

				$transacoes = $this->getUData("transacoes");

				$post["pagamento"] = $post["pagamento"] == "-1" ? "Boleto":"Cartão de Crédito";

				$transacoes[] = $post;

				$this->setUData(array("transacoes" => $transacoes));

				if($pagar["success"] == false){
					header("Location: /fechar_pedido/?houveumerro=1");
				} else {
					$this->setUData(["cupons" => $cupons]);
					$UserData->Set("carrinho", "{}");
					foreach($post["produtos"] as $prod){
						$prod2 = $this->loja_produtos("id={$prod["id"]}");
						if(count($prod2) > 0){
							parent::database()->setWhere("produtos", "id={$prod["id"]}", ["quantidade_estoque" => (string)((int)($prod2[0]["quantidade_estoque"]) - $prod["quantity"])]);
						}
					}
					header("Location: /minhas_compras/");
				}
			}

			$vars = ["enderecos" => "","cards" => "","TITLE" => "Fechar Pedido", "prodfisico" => ""];
			$enderecos = isset($this->sessao()->enderecos) ? $this->sessao()->enderecos:[];

			foreach($enderecos as $k=>$l){
				$vars["enderecos"] .= "<option value=\"{$k}\" data-apply='" . json_encode($l) . "'>Receber em {$l["rua"]}, {$l["numero"]} - {$l["bairro"]}</option>";
			}

			$cards = isset($this->sessao()->cards) ? $this->sessao()->cards:[];

			foreach($cards as $k=>$l){
				$isvalid = $this->pagarme()->isvalid->card->number($l["c_number"])
						&& $this->pagarme()->isvalid->card->cvv($l["c_cvv"])
						&& $this->pagarme()->isvalid->card->expires($l["c_expires"]);

				$card = [];

				$card["c_number"] = explode(" ", $l["c_number"]);
				$card["c_number"][0] = "";
				$card["c_number"][1] = "";
				$card["c_number"][2] = "";
				$card["c_number"] = implode("", $card["c_number"]);

				if($isvalid){
					$vars["cards"] .= "<option value='{$k}'>Credito: {$l["c_name"]} | Final {$card["c_number"]}</option>";
				} else {
					if(!empty($l["c_name"])){
						$vars["cards"] .= "<option disabled value='-1'>{$l["c_name"]} | Corrigir dados cadastrados</option>";
					}
				}
			}

			$vars["erro"] = isset($_GET["houveumerro"]) ? "{$_GET["houveumerro"]}":"0";

			exit($this->simple_loader($content, "site/fechar-pedido", $vars)->getCode());
		}

		function page_pesquisa(UITemplate $content){
			$produtos = $this->loja_produtos();

			for($i = 0; $i < count($produtos); $i++){
				$produtos[$i]["query_for_search"] = (($cat=$this->get_cat($p["categoria"])) !== false ? (
					($subcat=$this->get_subcat($p["subcategoria"])) !== false
						? "{$cat} {$subcat["txt"]}"
						: "{$cat}"
				) : "") . " {$produtos[$i]["nome"]} {$produtos[$i]["descricao-curta"]}";
			}

			$produtos = parent::database()->query($produtos, "query_for_search = %{$_GET["q"]}%");

			// $this->dbg($produtos);

			$produtos = $this->modelo_minhatura_produtos($content, $produtos, 32, "product mb-0", '<div class="col-12 col-sm-6 col-lg-3 mb-5">','</div>');

			exit($this->simple_loader($content, "site/pesquisa", ["TITLE" => "Pesquisa", "termo" => $_GET["q"], "resultado" => $produtos])->getCode());
		}

		function _calcular_frete(
            $cep_origem,  /* cep de origem, apenas numeros */
            $cep_destino, /* cep de destino, apenas numeros */
            $valor_declarado='0', /* indicar 0 caso não queira o valor declarado */
            $peso='1',        /* valor dado em Kg incluindo a embalagem. 0.1, 0.3, 1, 2 ,3 , 4 */
            $altura='15',      /* altura do produto em cm incluindo a embalagem */
            $largura='15',     /* altura do produto em cm incluindo a embalagem */
            $comprimento='15', /* comprimento do produto incluindo embalagem em cm */
            $cod_servico='pac' /* codigo do servico desejado */
            ){

            $cod_servico = strtoupper( $cod_servico );
            if( $cod_servico == 'SEDEX10' ) $cod_servico = 40215 ;
            if( $cod_servico == 'SEDEXACOBRAR' ) $cod_servico = 40045 ;
            if( $cod_servico == 'SEDEX' ) $cod_servico = 40010 ;
            if( $cod_servico == 'PAC' ) $cod_servico = 41106 ;

            # ###########################################
            # Código dos Principais Serviços dos Correios
            # 41106 PAC sem contrato
            # 40010 SEDEX sem contrato
            # 40045 SEDEX a Cobrar, sem contrato
            # 40215 SEDEX 10, sem contrato
            # ###########################################

            $correios = "http://ws.correios.com.br/calculador/CalcPrecoPrazo.aspx?nCdEmpresa=&sDsSenha=&sCepOrigem=".$cep_origem."&sCepDestino=".$cep_destino."&nVlPeso=".$peso."&nCdFormato=1&nVlComprimento=".$comprimento."&nVlAltura=".$altura."&nVlLargura=".$largura."&sCdMaoPropria=n&nVlValorDeclarado=".$valor_declarado."&sCdAvisoRecebimento=n&nCdServico=".$cod_servico."&nVlDiametro=0&StrRetorno=xml";

            // exit($correios);

            $xml = simplexml_load_file($correios);

            $_arr_ = array();
            if($xml->cServico->Erro == '0'):
                $_arr_['codigo'] = $xml -> cServico -> Codigo ;
                $_arr_['valor'] = $xml -> cServico -> Valor ;
                $_arr_['prazo'] = $xml -> cServico -> PrazoEntrega .' Dia(s)' ;
                // return $xml->cServico->Valor;
                return $_arr_ ;
            else:
                return false;
            endif;
        }

		function calcular_frete(){
            if(isset($_GET["consultaCEP"]) && $_GET["consultaCEP"] === "1"){
                foreach(array("largura","altura","comprimento") as $medida){
                    if((float)$_POST[$medida] < 15){
                        $_POST[$medida] = "15";
                    }
                }

                if((float)$_POST["peso"] < .3){
                    $_POST["peso"] = "0.3";
                }

                $this->type("application/json");

                //
                // exit(json_encode($_POST));

                exit(json_encode([$this->_calcular_frete(
                    $_POST["origem"],
                    $_POST["destino"],
                    $_POST["valor_declarado"],
                    $_POST["peso"],
                    $_POST["altura"],
                    $_POST["largura"],
                    $_POST["comprimento"],
                    "pac"
                ),$this->_calcular_frete(
                    $_POST["origem"],
                    $_POST["destino"],
                    $_POST["valor_declarado"],
                    $_POST["peso"],
                    $_POST["altura"],
                    $_POST["largura"],
                    $_POST["comprimento"],
                    "sedex"
                ),$this->_calcular_frete(
                    $_POST["origem"],
                    $_POST["destino"]
                )]));
            }
        }
	}
?>
