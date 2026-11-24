/* Narrow UI checks; runtime/lifecycle regression and browser acceptance are not repeated. */
'use strict';
const assert=require('node:assert/strict'),fs=require('node:fs');
const api=require('../../assets/js/anonymous-controller.js');
const {fixture,dom,node,response,drain}=require('./anonymous-fixtures.js');
let checks=0;function eq(a,b,m){assert.deepEqual(a,b,m);checks++;}
const prefix='pjh5pwp:anonymous:v1:'+encodeURIComponent('https://fixture.test/')+':';
async function setup(theme='site_default'){
 const f=fixture(undefined,{noStart:true});dom(f);const map=new Map([[prefix+'theme',JSON.stringify(theme)]]);
 f.win.localStorage={getItem:k=>map.get(k)??null,setItem:(k,v)=>map.set(k,v),removeItem:k=>map.delete(k)};f.map=map;
 f.controller=api.createGeneral(f.win,f.manifest,{version:1,page:'/lesson/',endpoint:f.manifest.endpoint,home:'https://fixture.test/'},f.manifest.players.map(player=>({player,nonce:'123456abcd'})));
 f.controller.start();await drain();f.requests[0].respond(response(f.requests[0]));await drain();return f;
}
(async()=>{
 const f=await setup();let status=node(f,'[role="status"]');
 eq(node(f,'details').hasAttribute('open'),false,'Initially collapsed');
 eq(status.getAttribute('data-pjh5pwp-routine'),'1','Routine success visually hidden when collapsed');
 eq(status.getAttribute('aria-live'),'polite','Accessible live status retained');
 eq(node(f,'legend').textContent,'H5P appearance','Semantic fieldset legend retained');
 eq(node(f,'select[id="pjh5pwp-anonymous-theme"]').children.some(o=>o.getAttribute('value')==='core'),false,'Core absent from normal choices');
 const css=fs.readFileSync('assets/css/anonymous-selector.css','utf8');
 eq(css.includes('details:not([open]) ~ [role="status"][data-pjh5pwp-routine="1"]'),true,'CSS hides only routine collapsed status');
 eq(css.includes('clip-path: inset(50%)'),true,'Visual clipping preserves accessibility');
 f.controller.refresh();const req=f.requests.at(-1);req.respond({}, {ok:false,status:403});await drain();
 eq(status.hasAttribute('data-pjh5pwp-routine'),false,'Errors remain visible');f.controller.stop();
 const core=await setup('core');const select=node(core,'select[id="pjh5pwp-anonymous-theme"]');
 eq(select.value,'core','Saved Core remains selected');eq(select.children[0].hasAttribute('disabled'),true,'Retained Core cannot be newly selected');
 eq(core.map.get(prefix+'theme'),'"core"','Saved Core never converted');
 const density=node(core,'select[id="pjh5pwp-anonymous-density"]');density.value='large';density.emit('change');await drain();
 eq(new URLSearchParams(core.requests.at(-1).init.body).get('theme'),'core','Density change preserves Core');core.controller.stop();
 const fallback=await setup('custom:deleted');status=node(fallback,'[role="status"]');eq(status.hasAttribute('data-pjh5pwp-routine'),false,'Fallback warnings remain visible');
 fallback.controller.refresh();const auth=fallback.requests.at(-1),b=response(auth);Object.assign(b,{audience:'authenticated',reload_required:true,reason:'authenticated_session',appearance:null,selection:null,catalog:null,fallback_reason:null});auth.respond(b,{ok:false,status:409});await drain();
 eq(status.hasAttribute('data-pjh5pwp-routine'),false,'Reload message remains visible');eq(status.textContent.includes('Reload'),true,'Reload instruction retained');fallback.controller.stop();
 console.log(`Anonymous interface: ${checks} focused assertions passed.`);
})().catch(e=>{console.error(e);process.exitCode=1;});
