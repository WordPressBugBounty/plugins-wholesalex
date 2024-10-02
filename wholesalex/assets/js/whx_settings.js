(()=>{"use strict";var e,t={5960:(e,t,l)=>{l.d(t,{$:()=>i,k:()=>s});var o=l(1594),a=l.n(o);const s=e=>{const[t,l]=(0,o.useReducer)(((e,t)=>{switch(t.type){case"ADD_MESSAGE":return[...e,t.payload];case"DELETE_MESSAGE":return e.length>0&&e.filter((e=>e.id!==t.payload));default:return e}}),[]);return a().createElement(i.Provider,{value:{state:t,dispatch:l}},e.children)},i=(0,o.createContext)()},8471:(e,t,l)=>{l.d(t,{A:()=>m});var o=l(1594),a=l.n(o),s=(l(6068),l(1827)),i=l(9039),r=l(8423),n=l(5945),p=l(3699),d=l(8341),x=l(3149),h=l(4778),c=l(1208),_=l(2646),g=l(44),f=l(5960),w=l(296);l(9575);const m=()=>{const[e,t]=(0,o.useState)([]),[l,m]=(0,o.useState)({}),[b,u]=(0,o.useState)(""),[y,v]=(0,o.useState)(!1),[k,A]=(0,o.useState)(!1),{dispatch:E}=(0,o.useContext)(f.$);(0,o.useEffect)((()=>{m(whx_settings.fields),t(whx_settings.data);const e=window.location.hash.slice(1);u(e&&whx_settings.fields[e]?e:"general")}),[]),(0,o.useEffect)((()=>{const e=()=>{const e=window.location.hash.slice(1);u(e&&l[e]?e:"general")};return window.addEventListener("hashchange",e),()=>{window.removeEventListener("hashchange",e)}}),[l]);const z=(t,l)=>null==e[t]?l.default:e[t],S=l=>{l.target.value.startsWith("pro_")?A(!0):"checkbox"===l.target.type?t({...e,[l.target.name]:l.target.checked?"yes":"no"}):t({...e,[l.target.name]:l.target.value})},C=l=>{l.preventDefault(),async function(){let l=arguments.length>0&&void 0!==arguments[0]?arguments[0]:"get";v(!0);let o={type:l,action:"settings_action",nonce:wholesalex.nonce,settings:e};wp.apiFetch({path:"/wholesalex/v1/settings_action",method:"POST",data:o}).then((e=>{if(e.success)if("get"==l){t(e.data.value),m(e.data.default);const l=window.location.hash.slice(1);u(l&&e.data.default[l]?l:"general")}else E({type:"ADD_MESSAGE",payload:{id:Date.now().toString(),type:"success",message:e.data}});else E({type:"ADD_MESSAGE",payload:{id:Date.now().toString(),type:"error",message:e.data}});v(!1)}))}("set")};return a().createElement("div",{className:"wholesalex_wrapper"},a().createElement("div",{className:"wholesalex_settings"},a().createElement("ul",{className:"wholesalex_settings_tab_lists"},Object.keys(l).map(((e,t)=>"_save"!==e&&l[e].attr&&Object.keys(l[e].attr).length>0&&a().createElement("li",{key:t,onClick:t=>{var l;u(l=e),window.location.hash=l},className:"wholesalex_settings_tab_list "+(b===e?"wholesalex_active_tab":"")},a().createElement("span",{className:"wholesalex_settings_tab__title"},l[e].label))))),(()=>{const o=l[b];return a().createElement("div",{className:"wholesalex_settings_tab"},a().createElement("div",{className:"wholesalex_settings__tab_header"},a().createElement("span",{className:"wholesalex_settings__tab_heading"},o?.label),a().createElement("button",{className:"wholesalex-btn wholesalex-primary-btn wholesalex-btn-lg",onClick:C},l._save?.label)),a().createElement("ul",{className:"wholesalex_settings__tab_content"},y&&a().createElement(_.A,null),o&&Object.keys(o.attr).map(((l,_)=>a().createElement("li",{key:`settings_field_content_${_}`,className:"choosebox"==o.attr[l].type?"wholesalex-choosebox wholesalex_settings__fields":"wholesalex_settings__fields"},a().createElement("div",{className:"wholesalex_settings__field"},"dynamic_rule_promo_section"===o.attr[l].type&&(l=>a().createElement("div",{className:"wholesalex_settings_dynamic_rule_section"},a().createElement("div",{className:"wholesalex_settings_d_ynamic_rule_section_title"},l.label),a().createElement("div",{className:"wholesalex_settings_dynamic_rule_section_fields"},l&&Object.keys(l.attr).map(((o,_)=>a().createElement("li",{key:`settings_field_content_${_}`,className:"choosebox"==l.attr[o].type?"wholesalex-choosebox wholesalex_settings__fields":"wholesalex_settings__fields"},a().createElement("div",{className:"wholesalex_settings__field"},"radio"===l.attr[o].type&&a().createElement(s.A,{key:o,label:l.attr[o].label,options:l.attr[o].options,name:o,value:z(o,l.attr[o]),onChange:S,defaultValue:l.attr[o].default,tooltip:l.attr[o].tooltip}),"select"===l.attr[o].type&&a().createElement(p.A,{key:o,label:l.attr[o].label,options:l.attr[o].options,name:o,value:z(o,l.attr[o]),onChange:S,defaultValue:l.attr[o].default,tooltip:l.attr[o].tooltip}),"switch"===l.attr[o].type&&a().createElement(i.A,{key:o,helpClass:"wholesalex_settings_help_text",className:"wholesalex_settings_field",label:l.attr[o].label,name:o,value:z(o,l.attr[o]),onChange:S,defaultValue:l.attr[o].default,desc:l.attr[o].desc,help:l.attr[o]?.help,tooltip:l.attr[o].tooltip}),"number"===l.attr[o].type&&a().createElement(r.A,{key:o,className:"wholesalex_settings_field",type:"number",label:l.attr[o].label,name:o,value:z(o,l.attr[o]),onChange:S,desc:l.attr[o].desc,help:l.attr[o]?.help,tooltip:l.attr[o].tooltip}),"text"===l.attr[o].type&&a().createElement(r.A,{key:o,className:"wholesalex_settings_field",type:"text",label:l.attr[o].label,name:o,value:z(o,l.attr[o]),onChange:S,desc:l.attr[o].desc,help:l.attr[o]?.help,tooltip:l.attr[o].tooltip,smart_tags:l.attr[o]?.smart_tags||!1}),"url"===l.attr[o].type&&a().createElement(r.A,{key:o,className:"wholesalex_settings_field",type:"url",label:l.attr[o].label,name:o,value:z(o,l.attr[o]),onChange:S,desc:l.attr[o].desc,help:l.attr[o]?.help,tooltip:l.attr[o].tooltip}),"color"===l.attr[o].type&&a().createElement(h.A,{key:o,type:"color",label:l.attr[o].label,name:o,value:e,setValue:t,defaultValue:l.attr[o].default,desc:l.attr[o].desc,help:l.attr[o]?.help,tooltip:l.attr[o].tooltip}),"textarea"===l.attr[o].type&&a().createElement(n.A,{key:o,label:l.attr[o].label,name:o,value:z(o,l.attr[o]),onChange:S,desc:l.attr[o].desc,help:l.attr[o]?.help,tooltip:l.attr[o].tooltip}),"DragList"===l.attr[o].type&&a().createElement(d.A,{label:l.attr[o].label,name:o,value:e,setValue:t,defaultValue:l.attr[o].default,desc:l.attr[o].desc,help:l.attr[o]?.help,options:z(o,l.attr[o]),tooltip:l.attr[o].tooltip}),"choosebox"===l.attr[o].type&&a().createElement(x.A,{label:l.attr[o].label,name:o,value:z(o,l.attr[o]),onChange:S,defaultValue:l.attr[o].default,desc:l.attr[o].desc,help:l.attr[o]?.help,options:l.attr[o].options,tooltip:l.attr[o].tooltip}),"shortcode"===l.attr[o].type&&a().createElement(c.A,{label:l.attr[o].label,name:o,value:e,setValue:t,defaultValue:l.attr[o].default,desc:l.attr[o].desc,help:l.attr[o]?.help,tooltip:l.attr[o].tooltip}))))))))(o.attr[l]),"radio"===o.attr[l].type&&a().createElement(s.A,{key:l,label:o.attr[l].label,options:o.attr[l].options,name:l,value:z(l,o.attr[l]),onChange:S,defaultValue:o.attr[l].default,tooltip:o.attr[l].tooltip}),"select"===o.attr[l].type&&a().createElement(p.A,{key:l,label:o.attr[l].label,options:o.attr[l].options,name:l,value:z(l,o.attr[l]),onChange:S,defaultValue:o.attr[l].default,tooltip:o.attr[l].tooltip}),"switch"===o.attr[l].type&&a().createElement(i.A,{key:l,helpClass:"wholesalex_settings_help_text",className:`wholesalex_settings_field ${o.attr[l].class}`,label:o.attr[l].label,name:l,value:z(l,o.attr[l]),onChange:S,defaultValue:o.attr[l].default,desc:o.attr[l].desc,help:o.attr[l]?.help,tooltip:o.attr[l].tooltip}),"number"===o.attr[l].type&&a().createElement(r.A,{key:l,className:"wholesalex_settings_field",type:"number",label:o.attr[l].label,name:l,value:z(l,o.attr[l]),onChange:S,desc:o.attr[l].desc,help:o.attr[l]?.help,tooltip:o.attr[l].tooltip}),"text"===o.attr[l].type&&a().createElement(r.A,{key:l,className:"wholesalex_settings_field",type:"text",label:o.attr[l].label,name:l,value:z(l,o.attr[l]),onChange:S,desc:o.attr[l].desc,help:o.attr[l]?.help,tooltip:o.attr[l].tooltip,smart_tags:o.attr[l]?.smart_tags||!1}),"url"===o.attr[l].type&&a().createElement(r.A,{key:l,className:"wholesalex_settings_field",type:"url",label:o.attr[l].label,name:l,value:z(l,o.attr[l]),onChange:S,desc:o.attr[l].desc,help:o.attr[l]?.help,tooltip:o.attr[l].tooltip}),"color"===o.attr[l].type&&a().createElement(h.A,{key:l,type:"color",label:o.attr[l].label,name:l,value:e,setValue:t,defaultValue:o.attr[l].default,desc:o.attr[l].desc,help:o.attr[l]?.help,tooltip:o.attr[l].tooltip}),"textarea"===o.attr[l].type&&a().createElement(n.A,{key:l,label:o.attr[l].label,name:l,value:z(l,o.attr[l]),onChange:S,desc:o.attr[l].desc,help:o.attr[l]?.help,tooltip:o.attr[l].tooltip}),"DragList"===o.attr[l].type&&a().createElement(d.A,{label:o.attr[l].label,name:l,value:e,setValue:t,defaultValue:o.attr[l].default,desc:o.attr[l].desc,help:o.attr[l]?.help,options:z(l,o.attr[l]),tooltip:o.attr[l].tooltip}),"choosebox"===o.attr[l].type&&a().createElement(x.A,{label:o.attr[l].label,name:l,value:z(l,o.attr[l]),onChange:S,defaultValue:o.attr[l].default,desc:o.attr[l].desc,help:o.attr[l]?.help,options:o.attr[l].options,tooltip:o.attr[l].tooltip}),"shortcode"===o.attr[l].type&&a().createElement(c.A,{label:o.attr[l].label,name:l,value:e,setValue:t,defaultValue:o.attr[l].default,desc:o.attr[l].desc,help:o.attr[l]?.help,tooltip:o.attr[l].tooltip})))))))})()),k&&a().createElement(g.A,{renderContent:()=>a().createElement(a().Fragment,null,a().createElement("img",{src:wholesalex.url+"/assets/img/unlock.svg",alt:"Unlock Icon"}),a().createElement("div",{className:"unlock_text"},whx_settings.i18n.unlock),a().createElement("div",{className:"unlock_heading"},whx_settings.i18n.unlock_heading),a().createElement("div",{className:"desc"},whx_settings.i18n.unlock_desc),a().createElement("a",{target:"_blank",className:"wholesalex-btn wholesalex-upgrade-pro-btn wholesalex-btn-lg",href:"https://getwholesalex.com/pricing/?utm_source=wholesalex-menu&utm_medium=email-unlock_features-upgrade_to_pro&utm_campaign=wholesalex-DB"},whx_settings.i18n.upgrade_to_pro)),onClose:()=>A(!1)}),a().createElement(w.A,{delay:3e3,position:"top_right"}))}},7735:(e,t,l)=>{var o=l(1594),a=l.n(o),s=l(5206),i=l.n(s),r=l(8471),n=l(6610),p=l(5960);document.addEventListener("DOMContentLoaded",(function(){document.body.contains(document.getElementById("_wholesalex_settings"))&&i().render(a().createElement(a().StrictMode,null,a().createElement(p.k,null,a().createElement(n.A,{title:whx_settings.i18n.settings}),a().createElement(r.A,null))),document.getElementById("_wholesalex_settings"))}))},94:(e,t,l)=>{l.d(t,{A:()=>r});var o=l(1601),a=l.n(o),s=l(6314),i=l.n(s)()(a());i.push([e.id,".wholesalex-drag-items{display:inline-flex;flex-direction:column;gap:10px}.wholesalex-drag-item{border:1px solid var(--wholesalex-border-color);padding:7px;width:200px;justify-content:space-between;font-size:var(--wholesalex-size-14);line-height:22px;color:var(--wholesalex-body-text-color);cursor:move;text-transform:capitalize}.wholesalex-drag-item .dashicons{margin-right:10px}.wholesalex_draglist{position:relative}.wholesalex_draglist .wholesalex-get-pro-button{position:absolute;left:60px;top:140px}.wholesalex-draglist-lock{position:absolute;font-size:150px;left:10px;opacity:0.5}\n",""]);const r=i},8588:(e,t,l)=>{l.d(t,{A:()=>r});var o=l(1601),a=l.n(o),s=l(6314),i=l.n(s)()(a());i.push([e.id,'.wholesalex_field__label{font-size:14px;font-weight:500;line-height:28px;text-align:left;color:var(--wholesalex-heading-text-color)}.wholesalex-heading__large{font-size:20px;line-height:22px;color:var(--wholesalex-heading-text-color);font-weight:500}.wholesalex_field_desc{font-size:14px;line-height:28px;color:var(--wholesalex-body-text-color);line-height:28px}.wholesalex_checkbox_field input[type=checkbox],.wholesalex_switch_field input[type=checkbox]{width:22px;height:20px;border-radius:2px;margin:0;position:relative;background-color:white;border:solid 1px #e9e9f0;margin-right:8px}.wholesalex_checkbox_field input[type=checkbox]:checked,.wholesalex_switch_field input[type=checkbox]:checked{background-color:#6c6cff;border:none}.wholesalex_checkbox_field input[type=checkbox]:checked::before,.wholesalex_switch_field input[type=checkbox]:checked::before{left:9px;top:2px;width:5px;height:10px;border:solid white;border-width:0 2px 2px 0;transform:rotate(45deg);border-radius:0;background:none;position:absolute;margin:0;padding:0}.wholesalex_checkbox_field input[type=checkbox]:focus,.wholesalex_switch_field input[type=checkbox]:focus{box-shadow:unset}.wholesalex_input_field input{padding:5px 0px 5px 15px;border-radius:2px;border:solid 1px #e9e9f0;background-color:#fff;width:100%;color:var(--wholesalex-body-text-color)}.wholesalex_input_field input:disabled{opacity:0.7}.wholesalex_radio_field{display:flex;flex-direction:column;text-align:left;gap:20px}.wholesalex_radio_field input[type=radio]{height:22px;border:solid 1px rgba(108,108,255,0.3);width:22px;background-color:white;margin:0;position:relative;margin-right:10px}.wholesalex_radio_field input[type=radio]:checked::before{position:absolute;content:"";border-radius:50%;width:14px;height:14px;background-color:var(--wholesalex-primary-color);left:50%;top:50%;margin-top:-7px;margin-left:-7px}.wholesalex_radio_field input[type=radio]:focus{box-shadow:unset}.wholesalex_radio_field .wholesalex_radio_field__options{display:flex;align-items:center;gap:30px;flex-wrap:wrap}.wholesalex_select_field select{border-radius:2px;border:solid 1px var(--wholesalex-border-color);background-color:#fff;color:var(--wholesalex-body-text-color);font-size:14px;line-height:22px;text-align:left;height:40px}.wholesalex_select_field.wholesalex_tier_field select{height:40px;width:100%}.wholesalex_choosebox_field{display:flex;flex-direction:column;gap:2px;grid-column:span 2}.wholesalex_choosebox_field .dashicons.dashicons-lock{position:absolute;top:5px;right:5px}.wholesalex_choosebox_field__options{display:flex;flex-wrap:wrap;gap:25px;position:relative}.wholesalex_choosebox_field__options>label{max-width:230px;position:relative;display:flex;align-items:center}.wholesalex_choosebox_field__options>label input[type=radio]{position:absolute;opacity:0}.wholesalex_choosebox_field__options>*{padding:10px !important;border:1px solid #eeeeee;border-radius:4px;margin:0px !important}.wholesalex_choosebox_field__options #choosebox-selected{border:1px solid black}.wholesalex_choosebox_field__options span.wholesalex-get-pro-button{position:absolute;bottom:5px;left:43%}.wholesalex_choosebox_field__options .wholesalex_choosebox_get_pro{position:absolute;top:50%;left:50%;transform:translate(-50%, -50%);text-transform:capitalize}.wholesalex-badge-style{flex-wrap:nowrap}.wholesalex_choosebox_field__options img{max-width:100%}.wholesalex_color_picker_field__content{display:inline-flex;align-items:center;text-transform:uppercase;padding:0;border:1px solid var(--wholesalex-border-color);gap:15px;padding-right:15px;border-radius:4px;overflow:hidden}.wholesalex_color_picker_field__content input[type=color]{width:44px;height:40px;padding:0;margin:0;border:none;opacity:0;cursor:pointer}.wholesalex_drag_drop_file_upload{display:flex;flex-direction:column;gap:10px}.wholesalex_drag_drop_file_upload__label{font-size:14px;font-weight:500;color:var(--wholesalex-heading-text-color);line-height:28px}input#wholesalex_file_upload{opacity:0;height:0px;width:0px;position:absolute;margin:0;padding:0;top:50%;right:50%}.wholesalex_drag_drop_file_upload__content_wrapper{border-radius:4px;border:dotted 1px var(--wholesalex-border-color);background-color:#fff;min-height:120px;align-items:center;justify-content:center;display:flex;font-weight:500}.wholesalex_cloud_upload_icon{font-size:40px;line-height:28px;width:40px;height:35px;color:#3b414d}.wholesalex_drag_drop_file_upload__content{display:flex;align-items:center;flex-direction:column;justify-content:center;color:var(--wholesalex-body-text-color);font-size:14px;line-height:28px}.wholesalex_file_upload__drag_active{background-color:#e5e5e5 !important}.wholesalex_link_text{color:var(--wholesalex-primary-color)}.wholesalex_slider{position:relative;display:inline-block;width:38px;height:20px;cursor:pointer;border-radius:10px}.wholesalex_slider__input{position:absolute !important;opacity:0 !important;width:0 !important;height:0 !important}.wholesalex_slider__trackpoint{position:absolute;top:50%;left:3px;width:14px;height:14px;background-color:white;border-radius:50%;transform:translateY(-50%);transition:transform 0.3s, background-color 0.3s, opacity 0.3s;transition-delay:0.1s}.wholesalex_slider__enabled{background-color:var(--wholesalex-primary-color);transition:opacity 0.3s}.wholesalex_slider__enabled .wholesalex_slider__trackpoint{transform:translateX(130%) translateY(-50%)}.wholesalex_slider__disabled{background-color:var(--wholesalex-primary-color);opacity:0.25;transition:opacity 0.3s}.wholesalex_slider__disabled .wholesalex_slider__trackpoint{transform:translateX(0%) translateY(-50%)}.wholesalex_slider_field .wholesalex_lock_icon{font-size:13px;line-height:20px;color:var(--wholesalex-primary-color);width:13px;position:absolute;left:21px}.wholesalex_slider_field_content{display:flex;align-items:center;max-width:38px;position:relative}.wholesalex_slider__locked .wholesalex_slider__trackpoint{margin-left:10px;animation:moveLeft 0.3s forwards;animation-delay:0.3s}@keyframes moveLeft{0%{margin-left:0}100%{margin-left:10px}}.wholesalex_search_field__content{display:flex;align-items:center;position:relative}.wholesalex_search_field__content input[type=text]{padding-right:30px}.wholesalex_search_icon{position:absolute;right:10px;font-size:24px;line-height:22px;color:var(--wholesalex-body-text-color)}\n',""]);const r=i},8521:(e,t,l)=>{l.d(t,{A:()=>r});var o=l(1601),a=l.n(o),s=l(6314),i=l.n(s)()(a());i.push([e.id,'.wholesalex_header .wholesalex_popup_menu{position:absolute;border-radius:2px;box-shadow:0 2px 4px 0 rgba(108,108,255,0.2);background-color:#fff;z-index:999;top:unset;right:6px;padding:15px;margin-top:30px;min-width:200px}.wholesalex_header .wholesalex_popup_menu::before{content:"";content:"\\f142";position:absolute;right:0px;top:-29px;font:normal 42px dashicons;color:#fff}.wholesalex_help_popup__link_label{color:var(--wholesalex-heading-text-color);text-decoration:none;font-size:14px;line-height:18px}.wholesalex_help_popup__link_label:hover{color:var(--wholesalex-primary-color)}.wholesalex_help_popup__link_label:focus{outline:none}.wholesalex_help_popup__links{animation:fadeIn 0.3s ease;margin:0px}@keyframes fadeIn{from{opacity:0}to{opacity:1}}.wholesalex_help_popup__link{text-decoration:none;line-height:1.5}.wholesalex_help_popup__list{display:flex;gap:9px;text-align:left;margin-bottom:15px}.wholesalex_help_popup__list:last-child{margin-bottom:0px}.wholesalex_help_popup__list .wholesalex_icon{font-size:14px;line-height:18px;display:flex;align-items:center;justify-content:center;padding:5px 5px 4px;background-color:var(--wholesalex-primary-color);color:#ffffff;border-radius:50%;width:14px;height:15px}@keyframes slide-in{0%{opacity:0;transform:translateY(-50%)}100%{opacity:1;transform:translateY(0)}}.wholesalex_logo{max-height:25px}.wholesalex_header_wrapper{display:block;background-color:white;text-align:center}.wholesalex_header{display:flex;margin:0 auto;justify-content:space-between;align-items:center;border-bottom:1px solid #e6e5e5}.wholesalex_header__left{display:flex;align-items:center;gap:15px;color:var(--wholesalex-primary-color);padding:14px 0px 14px 44px}.wholesalex_version{box-sizing:border-box;border:1px solid var(--wholesalex-primary-color);font-size:12px;line-height:1;padding:5px 10px 5px;border-radius:50px;align-items:center;font-weight:600}.wholesalex_right_arrow_icon{font-size:20px;height:20px;margin:0 5px}.wholesalex_header_help_icon{font-size:40px;width:35px;line-height:18px;color:var(--wholesalex-heading-text-color);cursor:pointer;padding:5px 20px}.wholesalex_header__right{border-left:1px solid #e6e5e5;padding:14px 0px 14px 0px;position:relative}.wholesalex_header__title{font-size:14px;font-weight:600}.wholesalex_backend_body.rtl .wholesalex_header__left{padding:14px 44px 14px 44px}\n',""]);const r=i},4437:(e,t,l)=>{l.d(t,{A:()=>r});var o=l(1601),a=l.n(o),s=l(6314),i=l.n(s)()(a());i.push([e.id,".wholesalex_circular_loading__wrapper{background-color:rgba(255,255,255,0.5);bottom:0;left:0;margin:0;position:absolute;right:0;top:0;transition:opacity 0.3s;z-index:9999;cursor:wait}.wholesalex_loading_spinner{margin-top:-21px;position:absolute;text-align:center;top:50%;width:100%}.wholesalex_circular_loading_icon{stroke-dasharray:90, 150;stroke-dashoffset:0;stroke-width:2;stroke:var(--wholesalex-primary-color);stroke-linecap:round;animation:wholesalex_circular_loading 1.5s ease-in-out infinite}@keyframes wholesalex_circular_loading{0%{stroke-dasharray:1, 140;stroke-dashoffset:0}}.wholesalex_loading_spinner .move_circular{animation:circular_rotate 2s linear infinite;height:42px;width:42px}@keyframes circular_rotate{100%{transform:rotate(1turn)}}\n",""]);const r=i},169:(e,t,l)=>{l.d(t,{A:()=>r});var o=l(1601),a=l.n(o),s=l(6314),i=l.n(s)()(a());i.push([e.id,".wholesalex_popup_menu{position:absolute;border-radius:4px;box-shadow:0 2px 4px 0 rgba(108,108,255,0.2);background-color:#fff;z-index:999;top:12px;right:10px;border:solid 1px var(--wholesalex-border-color);padding:0px 15px}.wholesalex_row_actions .wholesalex_popup_menu{min-width:150px;right:0;padding:0px 12px}.wholesalex_popup_menu__wrapper{position:relative}.wholesalex_dropdown{cursor:pointer}\n",""]);const r=i},3407:(e,t,l)=>{l.d(t,{A:()=>r});var o=l(1601),a=l.n(o),s=l(6314),i=l.n(s)()(a());i.push([e.id,".pro_popup_container{padding:50px 50px 40px 50px;border-radius:4px;box-shadow:0 50px 99px 0 rgba(62,51,51,0.5);background-color:#fff;max-width:520px;text-align:center;position:relative;max-height:90vh;overflow:auto;display:flex;align-items:center;justify-content:center;flex-direction:column;box-sizing:border-box;gap:25px}.wholesalex_get_pro_popup{position:relative;display:flex;gap:10px}.wholesalex_get_pro_popup_wrapper{display:flex;align-items:center;justify-content:center;position:fixed;top:0;left:0;width:100%;height:100%;z-index:999999;background:rgba(0,0,0,0.7);transition:all .15s}.wholesalex_get_pro_popup_wrapper .dashicons.dashicons-no-alt{height:40px;width:40px;background-color:#fff;padding:7px 7px 6px 7px;border-radius:50%;color:#091f36;font-size:26px;box-sizing:border-box;cursor:pointer}.wholesalex_get_pro_popup_wrapper .dashicons.dashicons-no-alt:hover{color:#a51818}\n",""]);const r=i},269:(e,t,l)=>{l.d(t,{A:()=>r});var o=l(1601),a=l.n(o),s=l(6314),i=l.n(s)()(a());i.push([e.id,".wholesalex-choosebox>.wholesalex-settings-wrap{flex-direction:column;gap:20px}.wholesalex_settings_tab_lists{text-align:left;max-width:270px;background-color:rgba(108,108,255,0.05);border-radius:4px;margin:0}.wholesalex_settings_tab_lists li{margin:0}.wholesalex_page_wholesalex-settings.rtl .wholesalex_settings_tab_lists{text-align:right}.wholesalex_page_wholesalex-settings.rtl .wholesalex_settings__fields{text-align:right}.wholesalex_settings_tab_list{padding:20px 25px;border-bottom:1px solid rgba(108,108,255,0.12);cursor:pointer;min-width:270px}.wholesalex_settings_tab_lists .wholesalex_active_tab{color:var(--wholesalex-primary-color);background-color:rgba(108,108,255,0.06)}.wholesalex_settings_tab__title{color:var(--wholesalex-heading-text-color);font-size:var(--wholesalex-size-14);line-height:var(--wholesalex-size-28);font-weight:500}.wholesalex_settings__tab_heading{font-size:20px;line-height:28px;color:var(--wholesalex-heading-text-color);font-weight:bold}.wholesalex_settings__tab_header{background-color:white;padding:20px 40px;text-align:left;border-bottom:1px solid rgba(108,108,255,0.2);display:flex;align-items:center;justify-content:space-between;max-height:28px}.wholesalex_settings_tab{box-shadow:0 1px 2px 0 rgba(108,108,255,0.1);background-color:#fff;width:100%}.wholesalex_settings{display:flex}.wholesalex_settings_field_label,.wholesalex_field__label{font-size:14px;font-weight:500;line-height:28px;text-align:left;color:var(--wholesalex-heading-text-color)}.wholesalex_settings_field_content{font-size:14px;line-height:28px;color:var(--wholesalex-body-text-color)}.wholesalex_settings__tab_content{padding:30px 50px;margin:0}.wholesalex_settings__fields{margin-bottom:45px;text-align:left}.wholesalex_settings__fields .wholesalex_switch_field,.wholesalex_settings__fields .wholesalex_input_field,.wholesalex_settings__fields .wholesalex_textarea_field,.wholesalex_settings__fields .wholesalex_select_field,.wholesalex_settings__fields .wholesalex_draglist_field,.wholesalex_settings__fields .wholesalex_shortcode_field{display:flex;gap:5%}.wholesalex_settings__fields .wholesalex_switch_field__label,.wholesalex_settings__fields .wholesalex_input_field__label,.wholesalex_settings__fields .wholesalex_textarea_field__label,.wholesalex_settings__fields .wholesalex_select_field__label,.wholesalex_settings__fields .wholesalex_draglist_field__label,.wholesalex_settings__fields .wholesalex_shortcode_field__label{width:30%}.wholesalex_settings__fields .wholesalex_switch_field__content,.wholesalex_settings__fields .wholesalex_input_field__content,.wholesalex_settings__fields .wholesalex_textarea_field__content,.wholesalex_settings__fields .wholesalex_select_field__content,.wholesalex_settings__fields .wholesalex_draglist_field__content{width:60%}.wholesalex_settings__fields .wholesalex_tooltip_icon{font-size:20px;line-height:26px;margin-top:1px;color:var(--wholesalex-heading-text-color);cursor:pointer}.wholesalex_settings__fields .wholesalex-tooltip-wrapper{width:19px}.wholesalex_page_wholesalex-settings select{min-width:250px}.wholesalex_field_desc{font-size:14px;color:var(--wholesalex-body-text-color)}.dashicons.wholesalex_not_migrated{color:#CDCFD5;font-size:26px;width:26px}.dashicons.wholesalex_migrated{color:var(--wholesalex-primary-color);font-size:26px;width:26px}.wholesalex_migration_field{display:flex;gap:15px}.wholesalex_migration_field .wholesalex_migration_field__label{font-size:14px;font-weight:500;text-align:left;color:var(--wholesalex-heading-text-color)}.wholesalex_migration_tab__footer{border-top:1px solid var(--wholesalex-border-color);padding:25px;text-align:right}button.wholesalex-btn.wholesalex-migrate-button[disabled]{background-color:#e9e9ea;background-image:unset;color:#858689;cursor:not-allowed}.wholesalex_migration__alert{background-color:var(--wholesalex-warning-button-color) !important;color:white;font-size:14px}.wholesalex_toast_message.wholesalex_migrating_notice{max-width:50%;margin:0 auto;margin-top:15px}.wholesalex_help_message .wholesalex_tooltip_icon{font-size:14px}.wholesalex-smart-tags-wrapper{display:flex;flex-direction:column;gap:5px;font-style:italic;color:var(--wholesalex-body-text-color)}.wholesalex_settings_d_ynamic_rule_section_title{font-size:large}.wholesalex_settings_dynamic_rule_section_fields{border:1px solid var(--wholesalex-border-color);padding-left:15px;padding-top:15px;margin-top:15px}.wholesalex_switch_field.wholesalex_settings_field.wsx-plugin-delete-setting{border:2px solid red;padding:5px;border-radius:8px;background-color:#ff9b9d}.wsx-plugin-delete-setting label.wholesalex_switch_field__desc.wholesalex_field_desc{color:#000000}textarea{width:100%}\n",""]);const r=i},8931:(e,t,l)=>{l.d(t,{A:()=>r});var o=l(1601),a=l.n(o),s=l(6314),i=l.n(s)()(a());i.push([e.id,".wholesalex_shortcode_field__content{display:flex;align-items:center;gap:5px;border-radius:4px;border:solid 1px rgba(108,108,255,0.2);background-image:linear-gradient(to bottom, rgba(108,108,255,0.2), rgba(71,71,217,0.15));width:fit-content;padding:5px;cursor:pointer;max-height:36px;box-sizing:border-box}.wholesalex_clipboard_icon{background-color:var(--wholesalex-primary-color);border-radius:4px;color:white;padding:5px 6px 4px;font-size:16px;line-height:22px}.wholesalex_get_shortcode_text{color:var(--wholesalex-heading-text-color);font-size:14px;line-height:22px;padding:0px 10px}.wholesalex_shortcode_field__content:hover{background-image:linear-gradient(to bottom, rgba(108,108,255,0.3), rgba(71,71,217,0.3))}\n",""]);const r=i},5567:(e,t,l)=>{l.d(t,{A:()=>r});var o=l(1601),a=l.n(o),s=l(6314),i=l.n(s)()(a());i.push([e.id,".wholesalex_toast_messages{display:flex;flex-direction:column;gap:10px;padding:10px;position:fixed;right:0px;z-index:999999;top:85px}.wholesalex_toast{position:absolute}.wholesalex-toaster{position:fixed;visibility:hidden;width:345px;background-color:#fefefe;height:76px;border-radius:4px;box-shadow:0px 0px 4px #9f9f9f;display:flex;align-items:center}.wholesalex-toaster span{display:block}.wholesalex-toaster .itm-center{font-size:var(--wholesalex-size-14)}.wholesalex-toaster .itm-last{padding:0 15px;margin-left:auto;height:100%;display:flex;align-items:center;border-left:1px solid #f2f2f2}.wholesalex-toaster .itm-last:hover{cursor:pointer;background-color:#f2f2f2}.wholesalex-toaster.show{visibility:visible;-webkit-animation:fadeinmessage 0.5s;animation:fadeinmessage 0.5s}@keyframes fadeinmessage{from{right:0;opacity:0}to{right:55px;opacity:1}}@keyframes slidefromright{from{transform:translateX(70px)}from{transform:translateX(-172px)}}.wholesalex__circle{stroke-dasharray:166;stroke-dashoffset:166;stroke-width:2;stroke-miterlimit:10;stroke:#7ac142;fill:none;animation:strokemessage 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards}.wholesalex-animation{width:45px;height:45px;border-radius:50%;display:block;stroke-width:2;margin:10px;stroke:#fff;stroke-miterlimit:10;box-shadow:inset 0px 0px 0px #7ac142;animation:fillmessage .4s ease-in-out .4s forwards, scalemessage .3s ease-in-out .9s both;margin-right:10px}.wholesalex__check{transform-origin:50% 50%;stroke-dasharray:48;stroke-dashoffset:48;animation:strokemessage 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards}.wholesalex__cross{stroke:red;fill:red}@keyframes strokemessage{100%{stroke-dashoffset:0}}@keyframes scalemessage{0%,100%{transform:none}50%{transform:scale3d(1.1, 1.1, 1)}}@keyframes fillmessage{100%{box-shadow:inset 0px 0px 0px 30px #7ac142}}.wholesalex_toast_message{padding:13px 14px 14px 15px;border-radius:4px;box-shadow:0 1px 2px 0 rgba(108,108,255,0.2);background-color:#fff;display:flex;max-width:380px;align-items:center;justify-content:center;min-width:15vw}.wholesalex_toast_message.show{visibility:visible;-webkit-animation:fadeinmessage 0.5s;animation:fadeinmessage 0.5s}.wholesalex_toast_message .toast_close{color:#091f36;font-size:18px;width:18px;height:19px;margin-left:auto;cursor:pointer}.wholesalex_toast_message .toast_close:hover{color:#690808}.wsx-error{padding:13px 14px 14px 15px;border-left:3px solid #d63638;box-shadow:0 1px 1px rgba(0,0,0,0.04)}span.dashicons.dashicons-smiley{font-size:22px;line-height:28px;color:#24be2a;width:22px;height:auto;margin-right:10px}span.message{font-size:14px;line-height:28px;color:#091f36}.top_right{right:50px;top:16%;animation:toast_slide_from_right 0.7s}@keyframes toast_slide_from_right{from{transform:translateX(100%)}to{translate:translateX(0)}}.wholesalex_delete_toast{transition:all 0.7s;transform:translateX(50%);opacity:0}\n",""]);const r=i},9675:(e,t,l)=>{l.d(t,{A:()=>r});var o=l(1601),a=l.n(o),s=l(6314),i=l.n(s)()(a());i.push([e.id,'.wholesalex-tooltip-wrapper{display:inline-block;position:relative;width:inherit;height:inherit}.wholesalex_tooltip{position:relative}.tooltip-content{width:250px;position:absolute;border-radius:4px;left:-125px;bottom:30px;padding:10px;background-color:black;color:white;font-size:14px;line-height:1.5;z-index:100}.tooltip-content::before{content:" ";left:50%;border:solid transparent;height:0;width:0;position:absolute;pointer-events:none;border-width:6px;margin-left:calc(6px * -1)}.tooltip-content.top::before{top:100%;border-top-color:black}.tooltip-content.right{left:calc(100% + 30px);top:50%;transform:translateX(0) translateY(-50%)}.tooltip-content.right::before{left:calc(6px * -1);top:50%;transform:translateX(0) translateY(-50%);border-right-color:black}.tooltip-content.bottom{bottom:calc(30px * -1)}.tooltip-content.bottom::before{bottom:100%;border-bottom-color:black}.tooltip-content.left{left:auto;right:calc(100% + 30px);top:50%;transform:translateX(0) translateY(-50%)}.tooltip-content.left::before{left:auto;right:calc(6px * -2);top:50%;transform:translateX(0) translateY(-50%);border-left-color:black}.tooltip-icon{width:inherit;height:inherit;font-size:28px}\n',""]);const r=i},6147:(e,t,l)=>{l.d(t,{A:()=>r});var o=l(1601),a=l.n(o),s=l(6314),i=l.n(s)()(a());i.push([e.id,".wholesalex_get_pro_popup img{max-width:103px}.with_premium_text{padding:9px 20px 7px 16px;border-radius:4px;border:dashed 1px #ffa471;color:#091f36;font-size:14px;line-height:26px}.desc{font-size:14px;color:#575a5d;line-height:24px;text-align:center}.unlock_text{font-size:14px;line-height:22px;text-transform:uppercase;color:#f2c736;font-weight:500}.addon_count{color:#091f36;font-size:20px;line-height:22px;font-weight:bold}.unlock_heading{color:#091f36;font-size:20px;line-height:22px;font-weight:bold}\n",""]);const r=i},5031:(e,t,l)=>{var o=l(5072),a=l.n(o),s=l(7825),i=l.n(s),r=l(7659),n=l.n(r),p=l(5056),d=l.n(p),x=l(540),h=l.n(x),c=l(1113),_=l.n(c),g=l(94),f={};f.styleTagTransform=_(),f.setAttributes=d(),f.insert=n().bind(null,"head"),f.domAPI=i(),f.insertStyleElement=h(),a()(g.A,f),g.A&&g.A.locals&&g.A.locals},7399:(e,t,l)=>{var o=l(5072),a=l.n(o),s=l(7825),i=l.n(s),r=l(7659),n=l.n(r),p=l(5056),d=l.n(p),x=l(540),h=l.n(x),c=l(1113),_=l.n(c),g=l(8588),f={};f.styleTagTransform=_(),f.setAttributes=d(),f.insert=n().bind(null,"head"),f.domAPI=i(),f.insertStyleElement=h(),a()(g.A,f),g.A&&g.A.locals&&g.A.locals},3520:(e,t,l)=>{var o=l(5072),a=l.n(o),s=l(7825),i=l.n(s),r=l(7659),n=l.n(r),p=l(5056),d=l.n(p),x=l(540),h=l.n(x),c=l(1113),_=l.n(c),g=l(8521),f={};f.styleTagTransform=_(),f.setAttributes=d(),f.insert=n().bind(null,"head"),f.domAPI=i(),f.insertStyleElement=h(),a()(g.A,f),g.A&&g.A.locals&&g.A.locals},9324:(e,t,l)=>{var o=l(5072),a=l.n(o),s=l(7825),i=l.n(s),r=l(7659),n=l.n(r),p=l(5056),d=l.n(p),x=l(540),h=l.n(x),c=l(1113),_=l.n(c),g=l(4437),f={};f.styleTagTransform=_(),f.setAttributes=d(),f.insert=n().bind(null,"head"),f.domAPI=i(),f.insertStyleElement=h(),a()(g.A,f),g.A&&g.A.locals&&g.A.locals},9878:(e,t,l)=>{var o=l(5072),a=l.n(o),s=l(7825),i=l.n(s),r=l(7659),n=l.n(r),p=l(5056),d=l.n(p),x=l(540),h=l.n(x),c=l(1113),_=l.n(c),g=l(169),f={};f.styleTagTransform=_(),f.setAttributes=d(),f.insert=n().bind(null,"head"),f.domAPI=i(),f.insertStyleElement=h(),a()(g.A,f),g.A&&g.A.locals&&g.A.locals},5030:(e,t,l)=>{var o=l(5072),a=l.n(o),s=l(7825),i=l.n(s),r=l(7659),n=l.n(r),p=l(5056),d=l.n(p),x=l(540),h=l.n(x),c=l(1113),_=l.n(c),g=l(3407),f={};f.styleTagTransform=_(),f.setAttributes=d(),f.insert=n().bind(null,"head"),f.domAPI=i(),f.insertStyleElement=h(),a()(g.A,f),g.A&&g.A.locals&&g.A.locals},6068:(e,t,l)=>{var o=l(5072),a=l.n(o),s=l(7825),i=l.n(s),r=l(7659),n=l.n(r),p=l(5056),d=l.n(p),x=l(540),h=l.n(x),c=l(1113),_=l.n(c),g=l(269),f={};f.styleTagTransform=_(),f.setAttributes=d(),f.insert=n().bind(null,"head"),f.domAPI=i(),f.insertStyleElement=h(),a()(g.A,f),g.A&&g.A.locals&&g.A.locals},1064:(e,t,l)=>{var o=l(5072),a=l.n(o),s=l(7825),i=l.n(s),r=l(7659),n=l.n(r),p=l(5056),d=l.n(p),x=l(540),h=l.n(x),c=l(1113),_=l.n(c),g=l(8931),f={};f.styleTagTransform=_(),f.setAttributes=d(),f.insert=n().bind(null,"head"),f.domAPI=i(),f.insertStyleElement=h(),a()(g.A,f),g.A&&g.A.locals&&g.A.locals},5787:(e,t,l)=>{var o=l(5072),a=l.n(o),s=l(7825),i=l.n(s),r=l(7659),n=l.n(r),p=l(5056),d=l.n(p),x=l(540),h=l.n(x),c=l(1113),_=l.n(c),g=l(5567),f={};f.styleTagTransform=_(),f.setAttributes=d(),f.insert=n().bind(null,"head"),f.domAPI=i(),f.insertStyleElement=h(),a()(g.A,f),g.A&&g.A.locals&&g.A.locals},7296:(e,t,l)=>{var o=l(5072),a=l.n(o),s=l(7825),i=l.n(s),r=l(7659),n=l.n(r),p=l(5056),d=l.n(p),x=l(540),h=l.n(x),c=l(1113),_=l.n(c),g=l(9675),f={};f.styleTagTransform=_(),f.setAttributes=d(),f.insert=n().bind(null,"head"),f.domAPI=i(),f.insertStyleElement=h(),a()(g.A,f),g.A&&g.A.locals&&g.A.locals},4748:(e,t,l)=>{var o=l(5072),a=l.n(o),s=l(7825),i=l.n(s),r=l(7659),n=l.n(r),p=l(5056),d=l.n(p),x=l(540),h=l.n(x),c=l(1113),_=l.n(c),g=l(6147),f={};f.styleTagTransform=_(),f.setAttributes=d(),f.insert=n().bind(null,"head"),f.domAPI=i(),f.insertStyleElement=h(),a()(g.A,f),g.A&&g.A.locals&&g.A.locals},1594:e=>{e.exports=React},5206:e=>{e.exports=ReactDOM}},l={};function o(e){var a=l[e];if(void 0!==a)return a.exports;var s=l[e]={id:e,exports:{}};return t[e].call(s.exports,s,s.exports,o),s.exports}o.m=t,e=[],o.O=(t,l,a,s)=>{if(!l){var i=1/0;for(d=0;d<e.length;d++){l=e[d][0],a=e[d][1],s=e[d][2];for(var r=!0,n=0;n<l.length;n++)(!1&s||i>=s)&&Object.keys(o.O).every((e=>o.O[e](l[n])))?l.splice(n--,1):(r=!1,s<i&&(i=s));if(r){e.splice(d--,1);var p=a();void 0!==p&&(t=p)}}return t}s=s||0;for(var d=e.length;d>0&&e[d-1][2]>s;d--)e[d]=e[d-1];e[d]=[l,a,s]},o.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return o.d(t,{a:t}),t},o.d=(e,t)=>{for(var l in t)o.o(t,l)&&!o.o(e,l)&&Object.defineProperty(e,l,{enumerable:!0,get:t[l]})},o.g=function(){if("object"==typeof globalThis)return globalThis;try{return this||new Function("return this")()}catch(e){if("object"==typeof window)return window}}(),o.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),o.r=e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},o.j=274,(()=>{var e={274:0};o.O.j=t=>0===e[t];var t=(t,l)=>{var a,s,i=l[0],r=l[1],n=l[2],p=0;if(i.some((t=>0!==e[t]))){for(a in r)o.o(r,a)&&(o.m[a]=r[a]);if(n)var d=n(o)}for(t&&t(l);p<i.length;p++)s=i[p],o.o(e,s)&&e[s]&&e[s][0](),e[s]=0;return o.O(d)},l=self.webpackChunkwholesalex=self.webpackChunkwholesalex||[];l.forEach(t.bind(null,0)),l.push=t.bind(null,l.push.bind(l))})(),o.nc=void 0;var a=o.O(void 0,[255,742],(()=>o(7735)));a=o.O(a)})();