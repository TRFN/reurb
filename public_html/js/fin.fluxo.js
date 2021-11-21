window.f_checksum = "";

window.url_checksum = "/pagamentos_data/";

window.__filtro__ = "nenhum";

window.filtroboleto = {
	aplicar: function(inicio=-1, fim=-1, status=-1, timming = 1e3){
		Swal.fire({
			position: 'bottom-end',
			title: '',
			html: '<i class="fa fa-sync fa-spin"></i>',
			showConfirmButton: false,
			customClass: "loadingMSG",
			timer: timming
	  	});

		setTimeout(()=>(__filtro__ = ["Boleto", inicio, fim, status]), timming * 0.25);
	},

	reset: function(){
		__filtro__ = "nenhum";
	},

	gets: function(){
		return __filtro__ === "nenhum" ? "":JSON.stringify(__filtro__);
	}
};

window.aplicar_filtro = function aplicar_filtro(data, imovel, pg, dt){
	if(__filtro__ == "nenhum"){
		return true;
	}

	typeof pg == "undefined" && (pg = "not");

	let tipo = __filtro__[0],
		data_inicio = __filtro__[1],
		data_fim = __filtro__[2],
		o_status = __filtro__[3];

	data_inicio = data_inicio == -1 ? new apiDate('{data1mesantes}'):new apiDate(data_inicio);
	data_fim = data_fim == -1 ? new apiDate('{data1mesdepois}'):new apiDate(data_fim);

	if(data===-1000){return [data_inicio, data_fim]}

	$("[data-name=\"data-inicio\"]").val(data_inicio.backup);
	$("[data-name=\"data-final\"]").val(data_fim.backup);

	if(tipo !== imovel["mtd-pgto"]){
		return false;
	}

	data = data.split("/"); data = new apiDate(`${data[2]}-${data[1]}-${data[0]}`);
	// console.log(data.time() > data_fim.time() || data.time() < data_inicio.time());
	if(data.time() > data_fim.time() || data.time() < data_inicio.time() ){
		return false;
	}
	// console.log(o_status != pg && status !== -1);
	if(o_status != pg && o_status !== -1){
		console.log(pg);
		return false;
	}

	dados_atuais.push(dt);

	return true;
}

window.mudar_status = function(ctx){
	$.post("/change_status/", {data: ctx}, ()=>$(".m-tooltip").remove());
};

window.apiDate = function(input){
    this.backup = input;
    this.input = new Date(typeof input !== "string" ? input : (input + "T12:00:00"));
    this.time = function(){ return this.input.getTime(); };
    this.get = function(){return this.input;};
    this._model = function(fn,val=1,op=1){
        return(this.input["set" + fn](this.input["get" + fn]() + (val*op)));
    };

    this.sumDay = function(q=1){
        return new Date(this._model("Date", q, 1));
    };

    this.sumMonth = function(q=1){
        return new Date(this._model("Month", q, 1));
    };

    this.sumYear = function(q=1){
        return new Date(this._model("Month", q*12, 1));
    };

    this.subDay = function(q=1){
        return new Date(this._model("Date", q, -1));
    };

    this.subMonth = function(q=1){
        return new Date(this._model("Month", q, -1));
    };

    this.subYear = function(q=1){
        return new Date(this._model("Month", q*12, -1));
    };
};

window.ano_atual = "{year}";

window.menor_data = [-1,-1];
window.maior_data = [-1,-1];

function carregar_arquivo_banco(input){
	if (input.files && input.files[0]) {
		const reader = new FileReader();
		const semelhanca = 30;
		reader.onload = (event) => {
			let data = event.target.result;
			data = data.split("CUSTAS (C)")[1];
			data = data.split("TOTAL")[0].trim();

			data = data.split("\r\n");

			for(i = 0; i < data.length; i++){
				data[i] = data[i].split('    ');
				n = [];
				for(j = 0; j < data[i].length; j++){
					data[i][j].length > 0 && n.push(data[i][j].trim());
				}
				n[1] = n[1].split(' ');
				n[1][2] = [
					("20"+(n[1][2].charAt(4)+n[1][2].charAt(5))),
					((n[1][2].charAt(2)+n[1][2].charAt(3))),
					((n[1][2].charAt(0)+n[1][2].charAt(1)))
				];
				n[1][1] = [
					("20"+(n[1][1].charAt(4)+n[1][1].charAt(5))),
					((n[1][1].charAt(2)+n[1][1].charAt(3))),
					((n[1][1].charAt(0)+n[1][1].charAt(1)))
				];

				data[i] = {
					id: n[1][0],
					nome: n[0],
					valor: parseFloat(n[2].split(/[^0-9\,]/).join('').split(",").join(".")),
					venc: `${n[1][1][2]}/${n[1][1][1]}/${n[1][1][0]}`,
					pgto: `${n[1][2][2]}/${n[1][2][1]}/${n[1][2][0]}`,
					dven: n[1][1].join("-"),
					dpgt: n[1][2].join("-")
				};

				data[i].json = JSON.stringify(data[i]);
			}

			dadosbanco = data;

			console.log(dadosbanco);

			$.post("/cli_list/", (data) => {
				let aplicado = [];
				for (var dado of dadosbanco) {
					let table = `<table data-id-dado='${dado.id}' data-fixd='{"cliente":null,"dados": ${dado.json}}' class="table table-bordered"><tbody><tr><td colspan=4 class='text-center align-middle'>O pagamento identificado por</td></tr><tr><td class='text-center align-middle'><b>NOME</b></td><td class='text-center align-middle'><b>VALOR</b></td><td class='text-center align-middle'><b>PGTO</b></td><td class='text-center align-middle'><b>VENC</b></td></tr><tr><td class='text-center align-middle'>${dado.nome}</td><td class='text-center align-middle'>${dado.valor}</td><td class='text-center align-middle'>${dado.pgto}</td><td class='text-center align-middle'>${dado.venc}</td></tr><tr><td colspan=4 class='text-center align-middle'><b>Deverá ser/ atribuído para:<b></td></tr><tr><td class='text-center align-middle'><b>NOME</b></td><td class='text-center align-middle'><b>DADOS</b></td><td class='text-center align-middle'><b>EMAIL</b></td><td class='text-center align-middle'><b>AÇÕES</b></td></tr>`;

					for (var cliente of data) {
						let t = cliente.nome.toLowerCase().split(''), c, p;

						c = (dado.nome.toLowerCase().split(''));
						p = 0;

						for (si=0;si<c.length;si++) {
							(t[si]==c[si])&&(p++);
						}
						p = (p / c.length)*100;

						p > semelhanca && console.log([cliente.nome,dado.nome,p]);

						if(p > semelhanca){
							table += `<tr id='cliente-${cliente.id}' class='clientes-compativeis'><td class='text-center align-middle'><b>${cliente.nome}</b></td><td class='text-center align-middle'>
								<b>CPF:</b>&nbsp;${cliente.doc}<br>
								<b>RG:</b>&nbsp;${cliente.rg}
							</td><td class='text-center align-middle'>${cliente.email}</td><td class="text-right text-end align-middle"><button onclick="window.selected = '${cliente.id}'; setTimeout(()=>swal.clickConfirm(),500);" class="btn m-btn btn-dark m-0 vinculos" data-cliente="${cliente.id}" data-dados='${dado.json}'>Selecionar</button></td></tr>`;
						}
					}

					table += "</tbody></table>";
					aplicado.push(table);
				}

				console.log(aplicado);

				let msgs = function(){
					let msg = aplicado.shift();
					window.data_auto = $(msg).data("fixd");
					console.log(msg);
					if($(msg).find(".clientes-compativeis").length > 0){
						swal.fire({
							title: "Confirme o vínculo para certificar que será feita a atribuição correta",
							html: msg,
							icon: "info",
							customClass: "swal-80-size",
							showCancelButton: true,
							denyButtonColor: '#124455',
							denyButtonText: 'Não Aplicar Este Dado',
							cancelButtonColor: '#551414',
							cancelButtonText: 'Cancelar Toda a Operação',
							showConfirmButton: false,
							showDenyButton: true
						}).then((response)=>{
							if(!response.isDismissed){
								if(response.isConfirmed){

									data_auto.cliente = selected;

									$.post("/aplicar_automatico/", {dado: JSON.stringify(data_auto)});

									applyCli.push(selected);

									applyBank[data_auto.dados.id] = selected;

								}

								if(aplicado.length){
									setTimeout(msgs,500);
								}
							}
						});
					} else {
						let data_apply = $(msg).data("id-dado");

						if(typeof data_apply == "undefined"){
							return history.go(0);
						}

						if(typeof applyBank[data_apply] !== "undefined"){
							cli = applyBank[data_apply];

							data_apply = $(msg).data("fixd");
							data_apply.cliente = cli;

							return $.post("/aplicar_automatico/", {dado: JSON.stringify(data_apply)});
						}

						setTimeout(msgs,500);
					}
				};

				msgs();
			});
		};
		reader.readAsText(input.files[0]);
	}
}



window.applyCli = [];
window.applyBank = [];

window.finupdt = ((callback=-1, args=-1)=>{
	args === -1 && (args = {});

	function g_money(data){
		return parseFloat(data.split(/[^0-9\,]/).join('').split(",").join("."));
	}

	function s_money(data){
		return data.toLocaleString('pt-br',{style: 'currency', currency: 'BRL'});
	}

	function ksort(obj){
	  var keys = Object.keys(obj).sort(function(a, b){return b-a})
	    , sortedObj = {};

	  for(var i in keys) {
	    sortedObj[4000-parseInt(keys[i])] = obj[keys[i]];
	  }
	  // console.log(sortedObj);
	  return Object.values(sortedObj);
	}

	$(".nav-item.nav-link:not(.mes-atual)").css({"font-weight": "400"});
	$(".nav-item.nav-link.active:not(.mes-atual)").css({"font-weight": "bold"});

	function proccess_data(data){
		if((data.checksum + filtroboleto.gets()) == f_checksum){
			return setTimeout(finupdt, 1500);
		}

		$(".tooltip.show.fade").remove();

		function atualizarTabelas(){
			for(let i = 1; i < 13; i++){
				let varr = "DataTable__" + (table_html=$(".mes-"+String(i)).find("table")).attr("id").split(/[^a-z]/).join('_');
				// console.log(varr);
				table = window[varr];

				$(".emdia-" + String(i)).text("0");
				$(".devendo-" + String(i)).text("0");

				table.rows().remove().draw(true);
			}
		}

		f_checksum = data.checksum + filtroboleto.gets();
		let anos = [];

		atualizarTabelas();

		window.dados_atuais = [];

		for(dado of data.valores){
			cliente = dado[0];
			conjuge = dado[1];
			imoveis = dado[2];

			for(imovel of imoveis){

				parcelas = typeof imovel["forma-pgto"] == "string" ? parseInt(imovel["forma-pgto"].split(/[^0-9]/).join("")):0;
				data = (new apiDate(imovel.data));
				for(k = 0; k < parcelas; k++){
					dt = data.get();
					mes = dt.getMonth() + 1;
					// console.log(mes);
					let varr = "DataTable__" + (table_html=$(".mes-" + String(mes)).find("table")).attr("id").split(/[^a-z]/).join('_');
					// console.log(varr);
					let table = window[varr], _ano,
						dta = ("0" + dt.getDate()).slice(-2) + "/" +
							  ("0"+(dt.getMonth()+1)).slice(-2) + "/" +
							  (_ano=dt.getFullYear()),
						dtb = (_ano) + "-" +
							  ("0"+(dt.getMonth()+1)).slice(-2) + "-" +
							  ("0" + dt.getDate()).slice(-2);

					anos.indexOf(_ano) < 0 && anos.push(_ano);

					let vz = imovel["forma-pgto"].split(/[^0-9]/).join('') + "x";
					let nparc = k+1;
					nparc = nparc < 10 ? "0" + String(nparc):nparc;

					parctxt = (typeof imovel.pgtos[dta] === "undefined" || imovel.pgtos[dta] != "pg") ? '<div class="d-block text-center mb-3 " data-skin="white" data-toggle="m-tooltip" data-placement="left" title="" data-original-title="Mudar para Pago"><span onclick="mudar_status(['+cliente.id+','+imovel.vendedor.id+','+imovel.id+',\''+dta+'\',1]); return false;" class="mt-3 text-uppercase badge badge-danger p-2" style="font-size: 12px; cursor: pointer; border-radius: 3px; text-shadow: 0px 0px 1px #0005;">Pendente</span><a class=d-block onclick="mudar_status(['+cliente.id+','+imovel.vendedor.id+','+imovel.id+',\''+dta+'\',1]); return false;">(Alterar)</a></div>'
					: '<div class="d-block text-center mb-3 " data-skin="white" data-toggle="m-tooltip" data-placement="left" title="" data-original-title="Mudar para Pendente"><span onclick="mudar_status(['+cliente.id+','+imovel.vendedor.id+','+imovel.id+',\''+dta+'\',0]); return false;" class="mt-3 text-uppercase badge badge-success p-2" style="font-size: 12px; cursor: pointer; border-radius: 3px; text-shadow: 0px 0px 1px #0005;">Pago</span><a class=d-block onclick="mudar_status(['+cliente.id+','+imovel.vendedor.id+','+imovel.id+',\''+dta+'\',0]); return false;">(Alterar)</a></div>';

					let estaparc = imovel["valor-parcela-"+String(k+1)];

					String(ano_atual) == String(_ano) && aplicar_filtro(dta,imovel,imovel.pgtos[dta],[dtb,dta,cliente.nome,imovel.vendedor.nome,`${imovel.rua}, ${imovel.numero}, ${imovel.bairro}`,imovel.produto,`Parcela ${nparc}`,((typeof imovel.pgtos[dta] === "undefined" || imovel.pgtos[dta] != "pg") ? 'Pendente':'Paga'),estaparc]) && (table.row.add(d=[
						`<strong class="data-tabela-ano-${_ano} d-block text-center">${dta}</strong>`,
						`<a target=_blank href="/cliente/editar/${cliente.id}/" href="" data-skin="white" data-toggle="m-tooltip" data-placement="top" title="" data-original-title="${cliente.nome}">${cliente.nome.substr(0, 12)}...</a>`,
						`<b>Vendedor:&nbsp;</b><a target=_blank href="/vendedor/editar/${imovel.vendedor.id}/" href="" data-skin="white" data-toggle="m-tooltip" data-placement="top" title="" data-original-title="${imovel.vendedor.nome}">${imovel.vendedor.nome.substr(0, 18)}&nbsp;...</a>
						<br>
						<b>Imóvel:&nbsp;</b><a target=_blank href="/imovel/editar/${imovel.id}/" href="" data-skin="white" data-toggle="m-tooltip" data-placement="top" title="" data-original-title="${imovel.rua}, ${imovel.numero}, ${imovel.bairro}">${imovel.rua.substr(0, 18)}&nbsp;...</a>
						<br>
						<b>Produto:&nbsp;</b>${imovel.produto}`,
						`${imovel["mtd-pgto"]},&nbsp;de ${vz}&nbsp;<br><b>Valor:</b>&nbsp;${estaparc}<br>Parcela&nbsp;${nparc}`,
						parctxt
					]).draw( true ));

					$("[data-parc=" + String(k+1) + "] .status").html(parctxt);
				data.sumMonth();
				}
			}
		}
		let anos_html = "";
		for(let _ano of anos){
			let sl = ano_atual == _ano ? 'selected="selected" ':'';
			anos_html += `<option ${sl} value=${_ano}>&nbsp;${_ano}&nbsp;</option>`;
		}

		$("#anos").html(anos_html);

		LWDKInitFunction.exec();

		setTimeout(finupdt, 250);

	}

	$.post(url_checksum, args, callback===-1 ? proccess_data:callback);
});

LWDKExec(()=>setTimeout(()=>(finupdt(), setTimeout(()=>$("#anos").change(function(){
	if($(this).val() === false)return;

	ano_atual = $(this).val();
	$(".ano").text(ano_atual);
	f_checksum = "upt";
}),1000)), 600));

LWDKExec(()=>$('a[data-toggle="tab"]').on('shown.bs.tab', function(e){
   $($.fn.dataTable.tables(true)).DataTable()
      .columns.adjust();
}));

LWDKExec(()=>($('.animated-btn > i').css({"transform":"scale(1.2)","transition":"all 600ms ease"}),$('.animated-btn').css({"border-radius":"2.5rem"}).animate({opacity:1}), setTimeout(()=>$('.animated-btn').each(function(i){
	$(this).find("> span").each(function(){
		$(this).data("orig-width", $(this).width());
	});
	let t = 150;
	$(this).find("> span").delay($(this).data("anim-delay")).animate({"width":"0px"}, t);
	$(this).find("> i").delay($(this).data("anim-delay")*2).css({"transform":"scale(1.6)"});
	$(this).delay($(this).data("anim-delay")).animate({"border-radius": "100%"}, t);
	// $(this).find("> i").css({"font-size": "18px"});
	$(this).mouseenter(function(){
		$(this).find("> span").css({"width":$(this).find("> span").data("orig-width")});
		$(this).css({"border-radius": "2.5rem"});
		$(this).find("> i").css({"transform":"scale(1.2)"});
	}).mouseleave(function(){
		$(this).find("> span").animate({"width":"0px"}, 300, ()=>($(this).animate({"border-radius": "100%"}, 100),$(this).find("> i").css({"transform":"scale(1.6)"})));
	});
}),1800)));

window.exportar_pagamentos = function(){
	let cfg = GetFormData("#exportar_pagamentos");

	cfg.dados = dados_atuais;

	// console.log([aplicar_filtro(-1000),'{umanoantes}','{umanodepois}','{data1mesantes}','{hoje}']);

	$.post("/pagamentos_gerar_relatorio/", cfg, function(data){
		location.href = "/" + data;
		setTimeout(()=>$.post("/deletar_excel/", {file:data}),2000);
	});
}
