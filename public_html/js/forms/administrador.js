LWDKExec(function(){
	$("input[type=\"checkbox\"]").each(function(){
		typeof (v=$(this).data("value")) == (typeof !0) && $(this).prop("checked", v);
	});

    FormCreateAction("form_submit", function(data){
        let go = function(to){
            to = /(#)/.test(to)?$(to)[0]:$("[data-name=\"" + to + "\"]")[0];

            setTimeout(()=>$(to).focus()[0].click(), 1100);

            $([document.documentElement, document.body]).animate({
                scrollTop: $(to).offset().top - 150
            }, 900);
        };

        if(data.nome.length < 3 || data.nome.split(" ").length < 2){
            return errorRequest(()=>go("nome"), "Insira um nome v&aacute;lido contendo nome e sobrenome.");
        }

        if(!Regex.Email.test(data.email)){
            return errorRequest(()=>go("email"), "Insira um email v&aacute;lido!");
        }

        if((data.senha.length > 0 && data.senha.length < 4 && "{acao}" == "modificar")||(data.senha.length < 4 && "{acao}" == "criar")){
            return errorRequest(()=>go("senha"), "Insira uma senha com ao menos 4 caracteres" + ("{acao}"=="modificar"?", <br>caso contr&aacute;rio, deixe em branco para n&atilde;o alterar.":"."));
        }

        $.post("{myurl}", data, function(success){
            if(success===true){
                successRequest(()=>Go("{page}/listar"));
            } else {
                errorRequest(refresh);
            }
        });
    });
	window.total = false;
	window.total1 = false;
	window.total2 = false;
	window.total3 = false;
	const step = function(){
		anyauth = false;
		for(let i = 0, inp; i < auths.length; i++){
			inp = $(`input.input-opt`).eq(i);
			!auths[i]
				? (inp.val(inp.data("value")).closest("label").removeClass("d-block").addClass("d-none"))
				: (inp.parent().removeClass("d-none").addClass("d-block"), anyauth = true);
		}

		$(".bg-secondary:not(.fixed)").each(function(){
			if($(this).find("label.d-block").length == 0){
				$(this).hide();
			}
		});

		!anyauth || "{id}" == "{sessao-id}" ? $(".permissoes").hide():$(".permissoes").show();

		let data = GetFormData("#form_submit"),
			state = function(__id,b=-1){
				let e = $(`input.input-opt[data-name="na_${__id}"]`);
				return b === -1 ? e:e.prop("checked", b);
			};
		if(data.na_ctrl_total){
			total = true;
			$("input.input-opt:not(:first)").prop("disabled", true).prop("checked", true);
		} else {
			if(total){
				$("input.input-opt:not(:first)").prop("disabled", false).prop("checked", false);
			} else {
				if(data.na_crud_cli||data.na_contratos||data.na_procuracoes||data.na_requerimentos){
					total1 = true;
					state("crud_imov", true).prop("disabled", true);
					data.na_crud_cli && state("recibo_cli", true).prop("disabled", true);
				} else if(total1) {
					total1 = false;
					state("crud_imov", false).prop("disabled", false);
					state("recibo_cli", false).prop("disabled", false);
				}

				if(data.na_pagamentos){
					total2 = true;
					state("boletos", true).prop("disabled", true);
					state("inadimplentes", true).prop("disabled", true);
				} else if(total2) {
					total2 = false;
					state("boletos", false).prop("disabled", false);
					state("inadimplentes", false).prop("disabled", false);
				}

				if(data.na_crud_fluxo){
					total3 = true;
					state("pagamentos", true).prop("disabled", true);
				} else if(total3) {
					total3 = false;
					state("pagamentos", false).prop("disabled", false);
				}
			}
			total = false;
		}

		setTimeout(step, 400);
	};

	setTimeout(step, 400);
});
