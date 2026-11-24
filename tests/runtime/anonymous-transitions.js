/* Focused audience transitions and anonymous bootstrap, using established DOM models. */
'use strict';
const assert=require('node:assert/strict');
const main=require('../../assets/js/theme-controller.js');
const anon=require('../../assets/js/anonymous-controller.js');
const {fixture,flush,envelope,P}=require('./theme-controller.js');
const {dom,response,drain,node}=require('./anonymous-fixtures.js');
let checks=0;function eq(a,b,m){assert.deepEqual(a,b,m);checks++;}
(async()=>{
 for(const audience of ['authenticated','default']) {
  const f=fixture(undefined,{noStart:true});dom(f);
  f.doc.body.append(f.el('script','',{'data-pjh5pwp-bootstrap':'1','data-pjh5pwp-audience':audience}));
  const c=main.create(f.win,f.manifest);assert.ok(c);c.start();await flush();
  const first=f.requests[0];eq(new URLSearchParams(first.init.body).get('expected_audience'),audience,'Expected audience sent');
  first.respond(envelope(first.id));await flush();eq(f.items[0].root.style.getPropertyValue(P),'#123','Appearance applied before transition');
  await f.advance(25000);const request=f.requests.at(-1),b=envelope(request.id);
  b.appearance={admission:{permitted:false,reason:'authentication_changed'},theme:null,color_declarations:'',density:null};
  request.respond(b,{ok:false,status:409});await drain();
  eq(f.items[0].root.style.getPropertyValue(P),'','Transition releases owned colors');
  eq(f.doc.querySelectorAll('[data-pjh5pwp-auth-reload]').length,1,'One manual reload notice');
  eq(f.timers.size,0,'No live controller handoff or renewal after transition');
  c.start();await flush();eq(f.requests.length,2,'Stopped controller cannot restart itself');
 }
 const f=fixture(undefined,{noStart:true});dom(f);
 const config={version:1,page:'/lesson/',endpoint:f.manifest.endpoint,home:'https://fixture.test/'};
 f.win.localStorage={getItem:()=>null,setItem(){},removeItem(){}};
 const script=f.doc.body.append(f.el('script','',{'data-pjh5pwp-anonymous':'1','data-pjh5pwp-anonymous-config':JSON.stringify(config)}));
 f.doc.body.append(f.el('span','',{'data-pjh5pwp-manifest':JSON.stringify(f.manifest)}));
 f.doc.body.append(f.el('span','',{'data-pjh5pwp-anonymous-grants':JSON.stringify(f.manifest.players.map(player=>({player,nonce:'123456abcd'})))}));
 const c=anon.bootstrap(f.win,script);await drain();eq(f.requests.length,1,'Real anonymous bootstrap starts one worker');
 f.requests[0].respond(response(f.requests[0]));await drain();eq(c.snapshot().state,'applied','Bootstrap endpoint response accepted');
 eq(node(f,'details').hasAttribute('open'),false,'Bootstrap disclosure remains closed after saved appearance');c.stop();
 f.win.pjh5pwpAnonymousAppearance={};eq(main.create(f.win,f.manifest),null,'Main controller refuses anonymous owner');
 console.log(JSON.stringify({checks,browser_acceptance:'deferred'}));
})().catch(e=>{console.error(e);process.exitCode=1;});
