(()=>{"use strict";var e,o={5960:(e,o,a)=>{a.d(o,{$:()=>n,k:()=>s});var l=a(1594),t=a.n(l);const s=e=>{const[o,a]=(0,l.useReducer)(((e,o)=>{switch(o.type){case"ADD_MESSAGE":return[...e,o.payload];case"DELETE_MESSAGE":return e.length>0&&e.filter((e=>e.id!==o.payload));default:return e}}),[]);return t().createElement(n.Provider,{value:{state:o,dispatch:a}},e.children)},n=(0,l.createContext)()},3133:(e,o,a)=>{a.d(o,{A:()=>r});var l=a(1594),t=a.n(l),s=(a(5007),a(530)),n=a(44),i=a(2646);const r=e=>{const{name:o,icon:a,docs:r,proUrl:d,features:p,moreFeature:c,video:x,setting_id:_,status:h,alt:m,desc:w,demo:g,is_pro:f,onUpdateStatus:u,id:b,onLockClick:y,depends_message:v}=e,k=e.lock_status,[E,A]=(0,l.useState)(!1),z=()=>{k?y(b):u(b,"no"===h||!h)};return t().createElement("div",{className:"wholesalex_addon_card_wrapper addon_card"},t().createElement("div",{className:"wholesalex_addon__card"},k&&t().createElement("span",{className:"dashicons dashicons-lock wholesalex_lock_icon wholesalex_icon",onClick:()=>y(b)}),t().createElement("div",{className:"wholesalex_addon_card__header"},t().createElement("img",{className:"wholesalex_card__img",src:a,alt:m||o}),t().createElement("span",{className:"wholesalex_card__title"},o)),t().createElement("div",{className:"wholesalex_addon_card__body"},t().createElement("div",{className:"wholesalex_card__desc"},w),p&&t().createElement("ul",{className:"wholesalex_addon_card__features"},p&&p.map((e=>t().createElement("li",{className:"wholesalex_addon_card__feature wholesalex_has_feature_checkbox"},"  ",e))),c&&t().createElement("li",{className:"wholesalex_addon_card__feature"},t().createElement("span",{className:"dashicons dashicons-plus wholesalex_add_more_icon wholesalex_icon"}),t().createElement("a",{href:c,className:"wholesalex_addon_card__add_more_link",target:"_blank"},"Much more features"))),v&&t().createElement("div",{className:"wholesalex_addon__depends_message",dangerouslySetInnerHTML:{__html:v}}))),t().createElement("div",{className:"wholesalex_addon_card_options"},t().createElement("div",{className:"wholesalex_addon_card_options__left"},e?.is_different_plugin&&!e.is_installed&&t().createElement("button",{className:"wholesalex-btn wholesalex-btn-primary wholesalex-separate-addon-install-button",onClick:()=>e.installPlugin(b)},b&&e?.loading[b]&&t().createElement(i.A,null),b&&e?.installText[b]),e?.is_different_plugin&&e.is_installed&&t().createElement(s.A,{name:b,key:b,value:!k&&h,onChange:z}),!e?.is_different_plugin&&t().createElement(s.A,{name:b,key:b,value:!k&&h,onChange:z})),t().createElement("div",{className:"wholesalex_addon_card_options__right"},g&&t().createElement("div",{className:"wholesalex_addon_card__demo_link"},t().createElement("span",{className:"dashicons dashicons-external wholesalex_external_icon wholesalex_icon"}),t().createElement("span",null,"Demo")),r&&t().createElement("a",{href:r,target:"_blank",className:"wholesalex_addon_card__docs_link"},t().createElement("div",{className:"wholesalex_addon_card__docs_link_content"},t().createElement("span",{className:"dashicons dashicons-media-document wholesalex_docs_icon wholesalex_icon"}),t().createElement("span",null,"Docs"))),x&&t().createElement("div",{className:"wholesalex_addon_card__video_link",onClick:()=>A(!0)},t().createElement("span",{className:"dashicons dashicons-admin-collapse wholesalex_video_icon wholesalex_icon"}),t().createElement("span",null,"Video")),_&&!e?.is_different_plugin&&t().createElement("a",{href:`${whx_addons.setting_url+_}`,target:"_blank",className:"wholesalex_addon_card__docs_link"},t().createElement("span",{className:"dashicons dashicons-admin-generic wholesalex_setting_icon wholesalex_icon"})),_&&e?.is_different_plugin&&e.is_installed&&h&&t().createElement("a",{href:`${whx_addons.setting_url+_}`,target:"_blank",className:"wholesalex_addon_card__docs_link"},t().createElement("span",{className:"dashicons dashicons-admin-generic wholesalex_setting_icon wholesalex_icon"})))),x&&E&&t().createElement(n.A,{renderContent:()=>t().createElement("div",{className:"wholesalex_popup_modal__addon_video_container"},t().createElement("iframe",{className:"wholesalex_popup_modal__addon_video ",width:"560",height:"315",src:x,frameBorder:"0",allow:"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen",title:o,loading:"lazy"})),onClose:()=>A(!1)}))}},598:(e,o,a)=>{a.d(o,{A:()=>c});var l=a(8168),t=a(1594),s=a.n(t),n=(a(62),a(9575)),i=a(3133),r=(a(400),a(5960)),d=a(296),p=a(44);const c=()=>{const[e,o]=(0,t.useState)(whx_addons.addons),{state:a,dispatch:c}=(0,t.useContext)(r.$),[x,_]=(0,t.useState)({wsx_addon_dokan_integration:"Install & Activate",wsx_addon_wcfm_integration:"Install & Activate"}),[h,m]=(0,t.useState)({wsx_addon_dokan_integration:!1,wsx_addon_wcfm_integration:!1}),[w,g]=(0,t.useState)(!1),[f,u]=(0,t.useState)(!1),b=["Get more pro addons & features.","Get regular updates.","Get dedicated support."],y=(a,l)=>{let t={...e};t[a]={...t[a],status:l},o(t),v("post","update_status",a,l)},v=async function(){let a=arguments.length>2&&void 0!==arguments[2]?arguments[2]:"",l=arguments.length>3&&void 0!==arguments[3]?arguments[3]:"",t={type:arguments.length>0&&void 0!==arguments[0]?arguments[0]:"post",action:"addons",request_for:"update_status",addon:a,status:l?"yes":"no",nonce:wholesalex.nonce};wp.apiFetch({path:"/wholesalex/v1/addons",method:"POST",data:t}).then((t=>{if(t.status)c({type:"ADD_MESSAGE",payload:{id:Date.now().toString(),type:"success",message:"Success"}});else{let t={...e};t[a]={...t[a],status:l},o(t)}}))},k=function(){let e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:"";_({...x,[e]:"Installing..."}),m({...h,[e]:!0});let o={type:"post",action:"addons",request_for:"install_plugin",addon:e,nonce:wholesalex.nonce};wp.apiFetch({path:"/wholesalex/v1/addons",method:"POST",data:o}).then((o=>{o.status?(c({type:"ADD_MESSAGE",payload:{id:Date.now().toString(),type:"success",message:o.data}}),_({...x,[e]:"Installed"}),window.location.reload()):c({type:"ADD_MESSAGE",payload:{id:Date.now().toString(),type:"success",message:"Error Occure! Please try again"}}),m({...h,[e]:!1})}))},E=e=>{"wsx_addon_whitelabel"===e?u(!0):g(!0)};return s().createElement("div",{className:"wholesalex_wrapper"},s().createElement("div",{className:"wholesalex_addon_sections"},!wholesalex?.is_pro_active&&s().createElement("section",{className:"wholesalex_addons"},s().createElement("div",{className:"wholesalex_addons__pro_card"},s().createElement("div",{className:"wholesalex_addons__pro_card_image"},s().createElement("img",{src:`${wholesalex.url}assets/img/addon_pro_image.png`})),s().createElement("div",{className:"wholesalex_addons__pro_card_content"},s().createElement("div",{className:"wholesalex_addons__pro_card_heading"},(0,n.__)("WholesaleX Pro","wholesalex")),s().createElement("div",{className:"wholesalex_addons__pro_card_desc"},(0,n.__)("Get started with the pro version of WholesaleX and Unlock more addons & features.","wholesalex")),s().createElement("ul",{className:"wholesalex_addons__pro_card_features"},b&&b.map((e=>s().createElement("li",{className:"wholesalex_addon_card__feature"}," ",s().createElement("span",{className:"wholesalex_icon feature_checkbox"})," ",e)))),s().createElement("a",{className:"wholesalex-btn wholesalex-upgrade-btn wholesalex-btn-lg",target:"_blank",href:"https://getwholesalex.com/pricing/?utm_source=wholesalex-menu&utm_medium=addons-upgrade_to_pro&utm_campaign=wholesalex-DB"},(0,n.__)("Upgrade to Pro  ➤","wholesalex"))))),s().createElement("section",{className:"wholesalex_addons"},s().createElement("div",{className:"wholesalex_addons__heading wholesalex_heading"},s().createElement("div",{className:"wholesalex-heading-text"},(0,n.nv)((0,n.__)("Ready to Grow Your Business With %s?","wholesalex"),wholesalex?.plugin_name)),s().createElement("div",{className:"wholesalex-text"},(0,n.__)("Use these conversion-focused features and earn more profit","wholesalex"))),s().createElement("div",{className:"wholesalex_addon__cards"},Object.keys(e).map((o=>s().createElement(i.A,(0,l.A)({},e[o],{id:o,alt:e[o].name,icon:e[o].img,onUpdateStatus:y,onLockClick:E,installPlugin:k,installText:x,loading:h})))))),w&&s().createElement(p.A,{renderContent:()=>s().createElement(s().Fragment,null,s().createElement("img",{src:wholesalex.url+"/assets/img/unlock.svg",alt:"Unlock Icon"}),s().createElement("div",{className:"unlock_text"},(0,n.__)("UNLOCK","wholesalex")),s().createElement("div",{className:"unlock_heading"},(0,n.__)("Unlock This Addon","wholesalex")),s().createElement("div",{className:"with_premium_text"},(0,n.__)("With WholesaleX Pro","wholesalex")),s().createElement("div",{className:"desc"},(0,n.__)("We are sorry, but unfortunately, this addon is unavailable in the free version. Please upgrade to a pro plan.","wholesalex")),s().createElement("a",{className:"wholesalex-btn wholesalex-upgrade-pro-btn wholesalex-btn-lg",href:"https://getwholesalex.com/pricing/?utm_source=wholesalex-menu&utm_medium=email-unlock_addon-upgrade_to_pro&utm_campaign=wholesalex-DB",target:"_blank"},(0,n.__)("Upgrade to Pro  ➤","wholesalex"))),onClose:()=>g(!1)}),f&&s().createElement(p.A,{renderContent:()=>s().createElement(s().Fragment,null,s().createElement("img",{src:wholesalex.url+"/assets/img/unlock.svg",alt:"Unlock Icon"}),s().createElement("div",{className:"unlock_text"},(0,n.__)("UNLOCK","wholesalex")),s().createElement("div",{className:"unlock_heading"},(0,n.__)("Unlock This Addon","wholesalex")),s().createElement("div",{className:"with_premium_text"},(0,n.__)("With WholesaleX Pro","wholesalex")),s().createElement("div",{className:"desc"},(0,n.__)("We are sorry, but unfortunately, this addon is unavailable in your current plan. Please upgrade to an agency plan.","wholesalex")),s().createElement("a",{className:"wholesalex-btn wholesalex-upgrade-pro-btn wholesalex-btn-lg",href:"https://getwholesalex.com/pricing/?utm_source=wholesalex-menu&utm_medium=WL_addons-upgrade_to_pro&utm_campaign=wholesalex-DB",target:"_blank"},(0,n.__)("Upgrade Now","wholesalex"))),onClose:()=>u(!1)})),s().createElement(d.A,{position:"top_right",delay:5e3}))}},6190:(e,o,a)=>{var l=a(1594),t=a.n(l),s=a(5206),n=a.n(s),i=a(598),r=a(6610),d=a(5960);document.addEventListener("DOMContentLoaded",(function(){document.body.contains(document.getElementById("wholesalex_addons_root"))&&n().render(t().createElement(t().StrictMode,null,t().createElement(d.k,null,t().createElement(r.A,{title:"Addons"}),t().createElement(i.A,null))),document.getElementById("wholesalex_addons_root"))}))},4047:(e,o,a)=>{a.d(o,{A:()=>i});var l=a(1601),t=a.n(l),s=a(6314),n=a.n(s)()(t());n.push([e.id,".wholesalex_addons__pro_card{border-radius:4px;box-shadow:0 1px 2px 0 rgba(8,68,129,0.2);background-color:#fff;padding:25px;display:flex;gap:40px;width:100%;margin-bottom:50px}.wholesalex_addons__pro_card .wholesalex_addons__pro_card_image{max-width:450px;line-height:1}.wholesalex_addons__pro_card_content{text-align:left;max-width:550px}.wholesalex_addons__pro_card_heading{color:var(--wholesalex-heading-text-color);line-height:var(--wholesalex-size-26);font-size:var(--wholesalex-size-20);font-weight:500}.wholesalex_addons__pro_card_desc{color:var(--wholesalex-body-text-color);font-size:var(--wholesalex-size-16);line-height:var(--wholesalex-size-24);padding-top:var(--wholesalex-size-15)}.wholesalex_addons__pro_card_features{padding-top:var(--wholesalex-size-20);margin:0;padding-bottom:var(--wholesalex-size-20)}.wholesalex_addon_card__feature{color:var(--wholesalex-body-text-color);line-height:var(--wholesalex-size-26);font-size:var(--wholesalex-size-14)}.wholesalex_addon_card__feature .feature_checkbox{background-size:14px;padding-left:24px}.wholesalex_addons{display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;gap:40px}.wholesalex_addon__cards{text-align:left;display:grid;grid-template-columns:repeat(auto-fit, minmax(375px, 1fr));width:100%;gap:30px}.wholesalex_addon__cards .pro_popup_container{max-width:unset}.wholesalex_card__title{text-align:left}.wholesalex_addon__card{display:flex;flex-direction:column}.wholesalex_card__title{font-weight:bold}.wholesalex_addon_card__body .wholesalex_card__desc{text-align:left;line-height:var(--wholesalex-size-26);margin-bottom:0px}.wholesalex_addon_card__body .wholesalex_addon__depends_message{margin-top:10px}.wholesalex_addon__card .wholesalex_card__img{margin-bottom:unset}.wholesalex_addon_card_options{padding:15px;border-top:1px solid var(--wholesalex-border-color);display:flex;justify-content:space-between;align-items:center;color:var(--wholesalex-body-text-color);font-size:var(--wholesalex-size-14);line-height:var(--wholesalex-size-28);gap:10px;position:relative}.wholesalex_addon_card_options__right{display:flex;gap:12px}.wholesalex_addon__card{padding:25px;position:relative}.wholesalex_addon__card .wholesalex_lock_icon{font-size:36px;height:37px;width:36px;position:absolute;right:0;top:0;line-height:28px;margin:15px}.wholesalex_lock_icon{color:#f78430;opacity:0.7}.wholesalex_addon_card_wrapper{box-shadow:0 1px 2px 0 rgba(108,108,255,0.2);background-color:#fff;border-radius:4px;display:flex;flex-direction:column;justify-content:space-between}.wholesalex_addon_card__header{display:flex;gap:16px;align-items:center}.wholesalex_add_more_icon{font-size:16px;line-height:26px;color:var(--wholesalex-primary-color);font-weight:bold;vertical-align:middle;width:13px;margin-right:10px;margin-top:-5px}.wholesalex_addon_card__add_more_link{color:var(--wholesalex-primary-color)}.wholesalex_addon_card__demo_link,.wholesalex_addon_card__docs_link_content,.wholesalex_addon_card__video_link{display:flex;gap:3px}.wholesalex_addon_card__docs_link{color:unset;text-decoration:none}.wholesalex_addon_card__docs_link:hover{color:var(--wholesalex-primary-color)}.wholesalex_addon_card__docs_link:focus{box-shadow:none;outline:0}.wholesalex_video_icon{transform:scale(-1)}.wholesalex_addon_card__video_link{align-items:flex-end}.wholesalex_external_icon,.wholesalex_docs_icon,.wholesalex_video_icon{font-size:16px;line-height:28px}.wholesalex_setting_icon{font-size:20px;line-height:28px;height:21px;width:20px}.wholesalex_addons__pro_card_image img{max-width:100%}.wholesalex-separate-addon-install-button{padding:10px}\n",""]);const i=n},4458:(e,o,a)=>{a.d(o,{A:()=>i});var l=a(1601),t=a.n(l),s=a(6314),n=a.n(s)()(t());n.push([e.id,".wholesalex_features__cards{display:grid;grid-template-columns:1fr 1fr 1fr;justify-items:center;gap:30px}@media (max-width: 768px){.wholesalex_features__cards{grid-template-columns:1fr 1fr}}@media (max-width: 576px){.wholesalex_features__cards{grid-template-columns:1fr}}.wholesalex_card{display:flex;flex-direction:column;align-items:center;justify-content:space-between;padding:40px 50px;max-width:385px;box-shadow:0 1px 2px 0 rgba(108,108,255,0.2);background-color:#fff;border-radius:4px}.wholesalex_card__img{height:54px;width:54px;border-radius:50%;background-color:rgba(108,108,255,0.1);margin-bottom:20px}.wholesalex_card__title{text-align:center;color:var(--wholesalex-heading-text-color);line-height:var(--wholesalex-size-26);font-size:var(--wholesalex-size-18);font-weight:500}.wholesalex_card__desc{text-align:center;color:var(--wholesalex-body-text-color);font-size:var(--wholesalex-size-14);line-height:var(--wholesalex-size-24);margin-top:15px;margin-bottom:30px}.wholesalex_addon_card__video_link{cursor:pointer}.wholesalex_addon_card__video_link:hover{color:var(--wholesalex-primary-color)}.wholesalex_addon__depends_message{font-size:14px;color:#d88a02}\n",""]);const i=n},8588:(e,o,a)=>{a.d(o,{A:()=>i});var l=a(1601),t=a.n(l),s=a(6314),n=a.n(s)()(t());n.push([e.id,'.wholesalex_field__label{font-size:14px;font-weight:500;line-height:28px;text-align:left;color:var(--wholesalex-heading-text-color)}.wholesalex-heading__large{font-size:20px;line-height:22px;color:var(--wholesalex-heading-text-color);font-weight:500}.wholesalex_field_desc{font-size:14px;line-height:28px;color:var(--wholesalex-body-text-color);line-height:28px}.wholesalex_checkbox_field input[type=checkbox],.wholesalex_switch_field input[type=checkbox]{width:22px;height:20px;border-radius:2px;margin:0;position:relative;background-color:white;border:solid 1px #e9e9f0;margin-right:8px}.wholesalex_checkbox_field input[type=checkbox]:checked,.wholesalex_switch_field input[type=checkbox]:checked{background-color:#6c6cff;border:none}.wholesalex_checkbox_field input[type=checkbox]:checked::before,.wholesalex_switch_field input[type=checkbox]:checked::before{left:9px;top:2px;width:5px;height:10px;border:solid white;border-width:0 2px 2px 0;transform:rotate(45deg);border-radius:0;background:none;position:absolute;margin:0;padding:0}.wholesalex_checkbox_field input[type=checkbox]:focus,.wholesalex_switch_field input[type=checkbox]:focus{box-shadow:unset}.wholesalex_input_field input{padding:5px 0px 5px 15px;border-radius:2px;border:solid 1px #e9e9f0;background-color:#fff;width:100%;color:var(--wholesalex-body-text-color)}.wholesalex_input_field input:disabled{opacity:0.7}.wholesalex_radio_field{display:flex;flex-direction:column;text-align:left;gap:20px}.wholesalex_radio_field input[type=radio]{height:22px;border:solid 1px rgba(108,108,255,0.3);width:22px;background-color:white;margin:0;position:relative;margin-right:10px}.wholesalex_radio_field input[type=radio]:checked::before{position:absolute;content:"";border-radius:50%;width:14px;height:14px;background-color:var(--wholesalex-primary-color);left:50%;top:50%;margin-top:-7px;margin-left:-7px}.wholesalex_radio_field input[type=radio]:focus{box-shadow:unset}.wholesalex_radio_field .wholesalex_radio_field__options{display:flex;align-items:center;gap:30px;flex-wrap:wrap}.wholesalex_select_field select{border-radius:2px;border:solid 1px var(--wholesalex-border-color);background-color:#fff;color:var(--wholesalex-body-text-color);font-size:14px;line-height:22px;text-align:left;height:40px}.wholesalex_select_field.wholesalex_tier_field select{height:40px;width:100%}.wholesalex_choosebox_field{display:flex;flex-direction:column;gap:2px;grid-column:span 2}.wholesalex_choosebox_field .dashicons.dashicons-lock{position:absolute;top:5px;right:5px}.wholesalex_choosebox_field__options{display:flex;flex-wrap:wrap;gap:25px;position:relative}.wholesalex_choosebox_field__options>label{max-width:230px;position:relative;display:flex;align-items:center}.wholesalex_choosebox_field__options>label input[type=radio]{position:absolute;opacity:0}.wholesalex_choosebox_field__options>*{padding:10px !important;border:1px solid #eeeeee;border-radius:4px;margin:0px !important}.wholesalex_choosebox_field__options #choosebox-selected{border:1px solid black}.wholesalex_choosebox_field__options span.wholesalex-get-pro-button{position:absolute;bottom:5px;left:43%}.wholesalex_choosebox_field__options .wholesalex_choosebox_get_pro{position:absolute;top:50%;left:50%;transform:translate(-50%, -50%);text-transform:capitalize}.wholesalex-badge-style{flex-wrap:nowrap}.wholesalex_choosebox_field__options img{max-width:100%}.wholesalex_color_picker_field__content{display:inline-flex;align-items:center;text-transform:uppercase;padding:0;border:1px solid var(--wholesalex-border-color);gap:15px;padding-right:15px;border-radius:4px;overflow:hidden}.wholesalex_color_picker_field__content input[type=color]{width:44px;height:40px;padding:0;margin:0;border:none;opacity:0;cursor:pointer}.wholesalex_drag_drop_file_upload{display:flex;flex-direction:column;gap:10px}.wholesalex_drag_drop_file_upload__label{font-size:14px;font-weight:500;color:var(--wholesalex-heading-text-color);line-height:28px}input#wholesalex_file_upload{opacity:0;height:0px;width:0px;position:absolute;margin:0;padding:0;top:50%;right:50%}.wholesalex_drag_drop_file_upload__content_wrapper{border-radius:4px;border:dotted 1px var(--wholesalex-border-color);background-color:#fff;min-height:120px;align-items:center;justify-content:center;display:flex;font-weight:500}.wholesalex_cloud_upload_icon{font-size:40px;line-height:28px;width:40px;height:35px;color:#3b414d}.wholesalex_drag_drop_file_upload__content{display:flex;align-items:center;flex-direction:column;justify-content:center;color:var(--wholesalex-body-text-color);font-size:14px;line-height:28px}.wholesalex_file_upload__drag_active{background-color:#e5e5e5 !important}.wholesalex_link_text{color:var(--wholesalex-primary-color)}.wholesalex_slider{position:relative;display:inline-block;width:38px;height:20px;cursor:pointer;border-radius:10px}.wholesalex_slider__input{position:absolute !important;opacity:0 !important;width:0 !important;height:0 !important}.wholesalex_slider__trackpoint{position:absolute;top:50%;left:3px;width:14px;height:14px;background-color:white;border-radius:50%;transform:translateY(-50%);transition:transform 0.3s, background-color 0.3s, opacity 0.3s;transition-delay:0.1s}.wholesalex_slider__enabled{background-color:var(--wholesalex-primary-color);transition:opacity 0.3s}.wholesalex_slider__enabled .wholesalex_slider__trackpoint{transform:translateX(130%) translateY(-50%)}.wholesalex_slider__disabled{background-color:var(--wholesalex-primary-color);opacity:0.25;transition:opacity 0.3s}.wholesalex_slider__disabled .wholesalex_slider__trackpoint{transform:translateX(0%) translateY(-50%)}.wholesalex_slider_field .wholesalex_lock_icon{font-size:13px;line-height:20px;color:var(--wholesalex-primary-color);width:13px;position:absolute;left:21px}.wholesalex_slider_field_content{display:flex;align-items:center;max-width:38px;position:relative}.wholesalex_slider__locked .wholesalex_slider__trackpoint{margin-left:10px;animation:moveLeft 0.3s forwards;animation-delay:0.3s}@keyframes moveLeft{0%{margin-left:0}100%{margin-left:10px}}.wholesalex_search_field__content{display:flex;align-items:center;position:relative}.wholesalex_search_field__content input[type=text]{padding-right:30px}.wholesalex_search_icon{position:absolute;right:10px;font-size:24px;line-height:22px;color:var(--wholesalex-body-text-color)}\n',""]);const i=n},8521:(e,o,a)=>{a.d(o,{A:()=>i});var l=a(1601),t=a.n(l),s=a(6314),n=a.n(s)()(t());n.push([e.id,'.wholesalex_header .wholesalex_popup_menu{position:absolute;border-radius:2px;box-shadow:0 2px 4px 0 rgba(108,108,255,0.2);background-color:#fff;z-index:999;top:unset;right:6px;padding:15px;margin-top:30px;min-width:200px}.wholesalex_header .wholesalex_popup_menu::before{content:"";content:"\\f142";position:absolute;right:0px;top:-29px;font:normal 42px dashicons;color:#fff}.wholesalex_help_popup__link_label{color:var(--wholesalex-heading-text-color);text-decoration:none;font-size:14px;line-height:18px}.wholesalex_help_popup__link_label:hover{color:var(--wholesalex-primary-color)}.wholesalex_help_popup__link_label:focus{outline:none}.wholesalex_help_popup__links{animation:fadeIn 0.3s ease;margin:0px}@keyframes fadeIn{from{opacity:0}to{opacity:1}}.wholesalex_help_popup__link{text-decoration:none;line-height:1.5}.wholesalex_help_popup__list{display:flex;gap:9px;text-align:left;margin-bottom:15px}.wholesalex_help_popup__list:last-child{margin-bottom:0px}.wholesalex_help_popup__list .wholesalex_icon{font-size:14px;line-height:18px;display:flex;align-items:center;justify-content:center;padding:5px 5px 4px;background-color:var(--wholesalex-primary-color);color:#ffffff;border-radius:50%;width:14px;height:15px}@keyframes slide-in{0%{opacity:0;transform:translateY(-50%)}100%{opacity:1;transform:translateY(0)}}.wholesalex_logo{max-height:25px}.wholesalex_header_wrapper{display:block;background-color:white;text-align:center}.wholesalex_header{display:flex;margin:0 auto;justify-content:space-between;align-items:center;border-bottom:1px solid #e6e5e5}.wholesalex_header__left{display:flex;align-items:center;gap:15px;color:var(--wholesalex-primary-color);padding:14px 0px 14px 44px}.wholesalex_version{box-sizing:border-box;border:1px solid var(--wholesalex-primary-color);font-size:12px;line-height:1;padding:5px 10px 5px;border-radius:50px;align-items:center;font-weight:600}.wholesalex_right_arrow_icon{font-size:20px;height:20px;margin:0 5px}.wholesalex_header_help_icon{font-size:40px;width:35px;line-height:18px;color:var(--wholesalex-heading-text-color);cursor:pointer;padding:5px 20px}.wholesalex_header__right{border-left:1px solid #e6e5e5;padding:14px 0px 14px 0px;position:relative}.wholesalex_header__title{font-size:14px;font-weight:600}.wholesalex_backend_body.rtl .wholesalex_header__left{padding:14px 44px 14px 44px}\n',""]);const i=n},4437:(e,o,a)=>{a.d(o,{A:()=>i});var l=a(1601),t=a.n(l),s=a(6314),n=a.n(s)()(t());n.push([e.id,".wholesalex_circular_loading__wrapper{background-color:rgba(255,255,255,0.5);bottom:0;left:0;margin:0;position:absolute;right:0;top:0;transition:opacity 0.3s;z-index:9999;cursor:wait}.wholesalex_loading_spinner{margin-top:-21px;position:absolute;text-align:center;top:50%;width:100%}.wholesalex_circular_loading_icon{stroke-dasharray:90, 150;stroke-dashoffset:0;stroke-width:2;stroke:var(--wholesalex-primary-color);stroke-linecap:round;animation:wholesalex_circular_loading 1.5s ease-in-out infinite}@keyframes wholesalex_circular_loading{0%{stroke-dasharray:1, 140;stroke-dashoffset:0}}.wholesalex_loading_spinner .move_circular{animation:circular_rotate 2s linear infinite;height:42px;width:42px}@keyframes circular_rotate{100%{transform:rotate(1turn)}}\n",""]);const i=n},169:(e,o,a)=>{a.d(o,{A:()=>i});var l=a(1601),t=a.n(l),s=a(6314),n=a.n(s)()(t());n.push([e.id,".wholesalex_popup_menu{position:absolute;border-radius:4px;box-shadow:0 2px 4px 0 rgba(108,108,255,0.2);background-color:#fff;z-index:999;top:12px;right:10px;border:solid 1px var(--wholesalex-border-color);padding:0px 15px}.wholesalex_row_actions .wholesalex_popup_menu{min-width:150px;right:0;padding:0px 12px}.wholesalex_popup_menu__wrapper{position:relative}.wholesalex_dropdown{cursor:pointer}\n",""]);const i=n},3407:(e,o,a)=>{a.d(o,{A:()=>i});var l=a(1601),t=a.n(l),s=a(6314),n=a.n(s)()(t());n.push([e.id,".pro_popup_container{padding:50px 50px 40px 50px;border-radius:4px;box-shadow:0 50px 99px 0 rgba(62,51,51,0.5);background-color:#fff;max-width:520px;text-align:center;position:relative;max-height:90vh;overflow:auto;display:flex;align-items:center;justify-content:center;flex-direction:column;box-sizing:border-box;gap:25px}.wholesalex_get_pro_popup{position:relative;display:flex;gap:10px}.wholesalex_get_pro_popup_wrapper{display:flex;align-items:center;justify-content:center;position:fixed;top:0;left:0;width:100%;height:100%;z-index:999999;background:rgba(0,0,0,0.7);transition:all .15s}.wholesalex_get_pro_popup_wrapper .dashicons.dashicons-no-alt{height:40px;width:40px;background-color:#fff;padding:7px 7px 6px 7px;border-radius:50%;color:#091f36;font-size:26px;box-sizing:border-box;cursor:pointer}.wholesalex_get_pro_popup_wrapper .dashicons.dashicons-no-alt:hover{color:#a51818}\n",""]);const i=n},5567:(e,o,a)=>{a.d(o,{A:()=>i});var l=a(1601),t=a.n(l),s=a(6314),n=a.n(s)()(t());n.push([e.id,".wholesalex_toast_messages{display:flex;flex-direction:column;gap:10px;padding:10px;position:fixed;right:0px;z-index:999999;top:85px}.wholesalex_toast{position:absolute}.wholesalex-toaster{position:fixed;visibility:hidden;width:345px;background-color:#fefefe;height:76px;border-radius:4px;box-shadow:0px 0px 4px #9f9f9f;display:flex;align-items:center}.wholesalex-toaster span{display:block}.wholesalex-toaster .itm-center{font-size:var(--wholesalex-size-14)}.wholesalex-toaster .itm-last{padding:0 15px;margin-left:auto;height:100%;display:flex;align-items:center;border-left:1px solid #f2f2f2}.wholesalex-toaster .itm-last:hover{cursor:pointer;background-color:#f2f2f2}.wholesalex-toaster.show{visibility:visible;-webkit-animation:fadeinmessage 0.5s;animation:fadeinmessage 0.5s}@keyframes fadeinmessage{from{right:0;opacity:0}to{right:55px;opacity:1}}@keyframes slidefromright{from{transform:translateX(70px)}from{transform:translateX(-172px)}}.wholesalex__circle{stroke-dasharray:166;stroke-dashoffset:166;stroke-width:2;stroke-miterlimit:10;stroke:#7ac142;fill:none;animation:strokemessage 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards}.wholesalex-animation{width:45px;height:45px;border-radius:50%;display:block;stroke-width:2;margin:10px;stroke:#fff;stroke-miterlimit:10;box-shadow:inset 0px 0px 0px #7ac142;animation:fillmessage .4s ease-in-out .4s forwards, scalemessage .3s ease-in-out .9s both;margin-right:10px}.wholesalex__check{transform-origin:50% 50%;stroke-dasharray:48;stroke-dashoffset:48;animation:strokemessage 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards}.wholesalex__cross{stroke:red;fill:red}@keyframes strokemessage{100%{stroke-dashoffset:0}}@keyframes scalemessage{0%,100%{transform:none}50%{transform:scale3d(1.1, 1.1, 1)}}@keyframes fillmessage{100%{box-shadow:inset 0px 0px 0px 30px #7ac142}}.wholesalex_toast_message{padding:13px 14px 14px 15px;border-radius:4px;box-shadow:0 1px 2px 0 rgba(108,108,255,0.2);background-color:#fff;display:flex;max-width:380px;align-items:center;justify-content:center;min-width:15vw}.wholesalex_toast_message.show{visibility:visible;-webkit-animation:fadeinmessage 0.5s;animation:fadeinmessage 0.5s}.wholesalex_toast_message .toast_close{color:#091f36;font-size:18px;width:18px;height:19px;margin-left:auto;cursor:pointer}.wholesalex_toast_message .toast_close:hover{color:#690808}.wsx-error{padding:13px 14px 14px 15px;border-left:3px solid #d63638;box-shadow:0 1px 1px rgba(0,0,0,0.04)}span.dashicons.dashicons-smiley{font-size:22px;line-height:28px;color:#24be2a;width:22px;height:auto;margin-right:10px}span.message{font-size:14px;line-height:28px;color:#091f36}.top_right{right:50px;top:16%;animation:toast_slide_from_right 0.7s}@keyframes toast_slide_from_right{from{transform:translateX(100%)}to{translate:translateX(0)}}.wholesalex_delete_toast{transition:all 0.7s;transform:translateX(50%);opacity:0}\n",""]);const i=n},9675:(e,o,a)=>{a.d(o,{A:()=>i});var l=a(1601),t=a.n(l),s=a(6314),n=a.n(s)()(t());n.push([e.id,'.wholesalex-tooltip-wrapper{display:inline-block;position:relative;width:inherit;height:inherit}.wholesalex_tooltip{position:relative}.tooltip-content{width:250px;position:absolute;border-radius:4px;left:-125px;bottom:30px;padding:10px;background-color:black;color:white;font-size:14px;line-height:1.5;z-index:100}.tooltip-content::before{content:" ";left:50%;border:solid transparent;height:0;width:0;position:absolute;pointer-events:none;border-width:6px;margin-left:calc(6px * -1)}.tooltip-content.top::before{top:100%;border-top-color:black}.tooltip-content.right{left:calc(100% + 30px);top:50%;transform:translateX(0) translateY(-50%)}.tooltip-content.right::before{left:calc(6px * -1);top:50%;transform:translateX(0) translateY(-50%);border-right-color:black}.tooltip-content.bottom{bottom:calc(30px * -1)}.tooltip-content.bottom::before{bottom:100%;border-bottom-color:black}.tooltip-content.left{left:auto;right:calc(100% + 30px);top:50%;transform:translateX(0) translateY(-50%)}.tooltip-content.left::before{left:auto;right:calc(6px * -2);top:50%;transform:translateX(0) translateY(-50%);border-left-color:black}.tooltip-icon{width:inherit;height:inherit;font-size:28px}\n',""]);const i=n},6147:(e,o,a)=>{a.d(o,{A:()=>i});var l=a(1601),t=a.n(l),s=a(6314),n=a.n(s)()(t());n.push([e.id,".wholesalex_get_pro_popup img{max-width:103px}.with_premium_text{padding:9px 20px 7px 16px;border-radius:4px;border:dashed 1px #ffa471;color:#091f36;font-size:14px;line-height:26px}.desc{font-size:14px;color:#575a5d;line-height:24px;text-align:center}.unlock_text{font-size:14px;line-height:22px;text-transform:uppercase;color:#f2c736;font-weight:500}.addon_count{color:#091f36;font-size:20px;line-height:22px;font-weight:bold}.unlock_heading{color:#091f36;font-size:20px;line-height:22px;font-weight:bold}\n",""]);const i=n},62:(e,o,a)=>{var l=a(5072),t=a.n(l),s=a(7825),n=a.n(s),i=a(7659),r=a.n(i),d=a(5056),p=a.n(d),c=a(540),x=a.n(c),_=a(1113),h=a.n(_),m=a(4047),w={};w.styleTagTransform=h(),w.setAttributes=p(),w.insert=r().bind(null,"head"),w.domAPI=n(),w.insertStyleElement=x(),t()(m.A,w),m.A&&m.A.locals&&m.A.locals},5007:(e,o,a)=>{var l=a(5072),t=a.n(l),s=a(7825),n=a.n(s),i=a(7659),r=a.n(i),d=a(5056),p=a.n(d),c=a(540),x=a.n(c),_=a(1113),h=a.n(_),m=a(4458),w={};w.styleTagTransform=h(),w.setAttributes=p(),w.insert=r().bind(null,"head"),w.domAPI=n(),w.insertStyleElement=x(),t()(m.A,w),m.A&&m.A.locals&&m.A.locals},7399:(e,o,a)=>{var l=a(5072),t=a.n(l),s=a(7825),n=a.n(s),i=a(7659),r=a.n(i),d=a(5056),p=a.n(d),c=a(540),x=a.n(c),_=a(1113),h=a.n(_),m=a(8588),w={};w.styleTagTransform=h(),w.setAttributes=p(),w.insert=r().bind(null,"head"),w.domAPI=n(),w.insertStyleElement=x(),t()(m.A,w),m.A&&m.A.locals&&m.A.locals},3520:(e,o,a)=>{var l=a(5072),t=a.n(l),s=a(7825),n=a.n(s),i=a(7659),r=a.n(i),d=a(5056),p=a.n(d),c=a(540),x=a.n(c),_=a(1113),h=a.n(_),m=a(8521),w={};w.styleTagTransform=h(),w.setAttributes=p(),w.insert=r().bind(null,"head"),w.domAPI=n(),w.insertStyleElement=x(),t()(m.A,w),m.A&&m.A.locals&&m.A.locals},9324:(e,o,a)=>{var l=a(5072),t=a.n(l),s=a(7825),n=a.n(s),i=a(7659),r=a.n(i),d=a(5056),p=a.n(d),c=a(540),x=a.n(c),_=a(1113),h=a.n(_),m=a(4437),w={};w.styleTagTransform=h(),w.setAttributes=p(),w.insert=r().bind(null,"head"),w.domAPI=n(),w.insertStyleElement=x(),t()(m.A,w),m.A&&m.A.locals&&m.A.locals},9878:(e,o,a)=>{var l=a(5072),t=a.n(l),s=a(7825),n=a.n(s),i=a(7659),r=a.n(i),d=a(5056),p=a.n(d),c=a(540),x=a.n(c),_=a(1113),h=a.n(_),m=a(169),w={};w.styleTagTransform=h(),w.setAttributes=p(),w.insert=r().bind(null,"head"),w.domAPI=n(),w.insertStyleElement=x(),t()(m.A,w),m.A&&m.A.locals&&m.A.locals},5030:(e,o,a)=>{var l=a(5072),t=a.n(l),s=a(7825),n=a.n(s),i=a(7659),r=a.n(i),d=a(5056),p=a.n(d),c=a(540),x=a.n(c),_=a(1113),h=a.n(_),m=a(3407),w={};w.styleTagTransform=h(),w.setAttributes=p(),w.insert=r().bind(null,"head"),w.domAPI=n(),w.insertStyleElement=x(),t()(m.A,w),m.A&&m.A.locals&&m.A.locals},5787:(e,o,a)=>{var l=a(5072),t=a.n(l),s=a(7825),n=a.n(s),i=a(7659),r=a.n(i),d=a(5056),p=a.n(d),c=a(540),x=a.n(c),_=a(1113),h=a.n(_),m=a(5567),w={};w.styleTagTransform=h(),w.setAttributes=p(),w.insert=r().bind(null,"head"),w.domAPI=n(),w.insertStyleElement=x(),t()(m.A,w),m.A&&m.A.locals&&m.A.locals},7296:(e,o,a)=>{var l=a(5072),t=a.n(l),s=a(7825),n=a.n(s),i=a(7659),r=a.n(i),d=a(5056),p=a.n(d),c=a(540),x=a.n(c),_=a(1113),h=a.n(_),m=a(9675),w={};w.styleTagTransform=h(),w.setAttributes=p(),w.insert=r().bind(null,"head"),w.domAPI=n(),w.insertStyleElement=x(),t()(m.A,w),m.A&&m.A.locals&&m.A.locals},4748:(e,o,a)=>{var l=a(5072),t=a.n(l),s=a(7825),n=a.n(s),i=a(7659),r=a.n(i),d=a(5056),p=a.n(d),c=a(540),x=a.n(c),_=a(1113),h=a.n(_),m=a(6147),w={};w.styleTagTransform=h(),w.setAttributes=p(),w.insert=r().bind(null,"head"),w.domAPI=n(),w.insertStyleElement=x(),t()(m.A,w),m.A&&m.A.locals&&m.A.locals},1594:e=>{e.exports=React},5206:e=>{e.exports=ReactDOM}},a={};function l(e){var t=a[e];if(void 0!==t)return t.exports;var s=a[e]={id:e,exports:{}};return o[e].call(s.exports,s,s.exports,l),s.exports}l.m=o,e=[],l.O=(o,a,t,s)=>{if(!a){var n=1/0;for(p=0;p<e.length;p++){a=e[p][0],t=e[p][1],s=e[p][2];for(var i=!0,r=0;r<a.length;r++)(!1&s||n>=s)&&Object.keys(l.O).every((e=>l.O[e](a[r])))?a.splice(r--,1):(i=!1,s<n&&(n=s));if(i){e.splice(p--,1);var d=t();void 0!==d&&(o=d)}}return o}s=s||0;for(var p=e.length;p>0&&e[p-1][2]>s;p--)e[p]=e[p-1];e[p]=[a,t,s]},l.n=e=>{var o=e&&e.__esModule?()=>e.default:()=>e;return l.d(o,{a:o}),o},l.d=(e,o)=>{for(var a in o)l.o(o,a)&&!l.o(e,a)&&Object.defineProperty(e,a,{enumerable:!0,get:o[a]})},l.g=function(){if("object"==typeof globalThis)return globalThis;try{return this||new Function("return this")()}catch(e){if("object"==typeof window)return window}}(),l.o=(e,o)=>Object.prototype.hasOwnProperty.call(e,o),l.r=e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},l.j=68,(()=>{var e={68:0};l.O.j=o=>0===e[o];var o=(o,a)=>{var t,s,n=a[0],i=a[1],r=a[2],d=0;if(n.some((o=>0!==e[o]))){for(t in i)l.o(i,t)&&(l.m[t]=i[t]);if(r)var p=r(l)}for(o&&o(a);d<n.length;d++)s=n[d],l.o(e,s)&&e[s]&&e[s][0](),e[s]=0;return l.O(p)},a=self.webpackChunkwholesalex=self.webpackChunkwholesalex||[];a.forEach(o.bind(null,0)),a.push=o.bind(null,a.push.bind(a))})(),l.nc=void 0;var t=l.O(void 0,[255,742],(()=>l(6190)));t=l.O(t)})();