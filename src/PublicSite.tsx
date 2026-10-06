import { useState, useEffect, type FormEvent } from 'react';
import { ArrowUpRight, Beer, CalendarDays, Clock3, MapPin, Menu, X, Music2, Utensils, Instagram, Phone, Mail, Sparkles, Search, ChevronLeft, ChevronRight, MessageCircle } from 'lucide-react';
import { motion, AnimatePresence } from 'motion/react';
import { initial, url, asset, whatsapp, money, dateLabel, api } from './lib/api';
import { Reveal, Modal, SectionHeading } from './components/ui';
import { TextMorph } from './components/motion-primitives/text-morph';
import { ShimmerButton } from './components/watermelon/shimmer-button';
import type { Entry, SiteData } from './types';

const navigation = [
  ['home','Início','index.php'], ['about','Nossa história','sobre.php'], ['menu','Cardápio','cardapio.php'],
  ['events','Agenda','eventos.php'], ['gallery','Galeria','galeria.php'], ['contact','Contato','contato.php'],
];
const optimized = (path?: string) => path?.startsWith('assets/uploads/bar-') ? asset(path.replace('.jpeg','.webp')) : asset(path);
const safeSocial = (value?: string) => value && /^https?:\/\//i.test(value) ? value : '';
const plainStory = (value: string) => value.replace(/\s*Edite este texto.*$/,'');

export default function PublicSite() {
  const [data,setData]=useState<SiteData>(initial);
  const [mobile,setMobile]=useState(false);
  const {settings:s,page}=data;
  useEffect(()=>{
    let alive=true;
    api<SiteData>('public').then(next=>{if(alive)setData(old=>({...old,...next}));}).catch(()=>{});
    return ()=>{alive=false;};
  },[]);
  const title = s.hero_line1 + ' ' + s.hero_line2 + ' ' + s.hero_line3;
  return <div className="public-site">
    <a className="skip-link" href="#conteudo">Pular para o conteúdo</a>
    <div className="announcement"><span>Um brinde aos bons encontros.</span><span><Clock3 size={13}/>{s.horario}</span></div>
    <header className="site-header"><div className="container nav-wrap">
      <a className="brand" href={url('index.php')} aria-label={s.nome + ' — início'}><img src={asset(!s.logo_image || s.logo_image==='assets/img/logo.png' ? 'assets/img/logo.webp' : s.logo_image)} alt="" width="62" height="62"/><span>Encontro<br/><small>dos Amigos</small></span></a>
      <nav aria-label="Navegação principal" className="desktop-nav">{navigation.map(([key,label,path])=><a key={key} href={url(path)} className={page===key || (page==='event' && key==='events') ? 'active' : ''} aria-current={page===key ? 'page' : undefined}>{label}</a>)}</nav>
      <a className="button nav-contact" href={whatsapp(s)} target="_blank" rel="noopener noreferrer">Vamos conversar<ArrowUpRight size={16}/></a>
      <button className="icon-button mobile-toggle" aria-expanded={mobile} aria-controls="mobile-menu" aria-label={mobile ? 'Fechar menu' : 'Abrir menu'} onClick={()=>setMobile(!mobile)}>{mobile ? <X/> : <Menu/>}</button>
    </div>
    <AnimatePresence>{mobile && <motion.nav id="mobile-menu" className="mobile-nav" aria-label="Menu móvel" initial={{height:0,opacity:0}} animate={{height:'auto',opacity:1}} exit={{height:0,opacity:0}}>{navigation.map(([key,label,path])=><a key={key} href={url(path)} aria-current={page===key ? 'page' : undefined} onClick={()=>setMobile(false)}>{label}<ArrowUpRight size={16}/></a>)}</motion.nav>}</AnimatePresence>
    </header>
    <main id="conteudo">
      {page==='home' && <>
        <section className="hero container"><Reveal className="hero-copy">
          <p className="eyebrow"><span/> {s.cidade || 'Seu próximo encontro'}</p>
          <h1>{title.toLocaleLowerCase('pt-BR').replace(/^./,c=>c.toUpperCase())}<em>Com os amigos.</em></h1>
          <p className="hero-description">{s.hero_subtitle}</p>
          <div className="hero-actions"><a className="button amber" href={url('cardapio.php')}>Conheça o cardápio<ArrowUpRight size={18}/></a><a className="button outline" href={whatsapp(s)} target="_blank" rel="noopener noreferrer"><MessageCircle size={18}/>Chame a gente</a></div>
          <div className="hero-small"><div className="hero-icons"><Beer size={18}/><Utensils size={18}/><Music2 size={18}/></div><p>Boa comida. Boas histórias.<br/><strong>Bons amigos.</strong></p></div>
        </Reveal>
        <Reveal className="hero-visual" delay={.15}><img className="hero-shapes" src={asset('assets/img/encontro-shapes.svg')} alt="" aria-hidden="true"/><div className="hero-photo"><img src={optimized(s.hero_image)} alt="Interior do Bar Encontro dos Amigos, com balcão e decoração da casa" fetchPriority="high"/><div className="photo-caption"><span className="live-dot"/><span>Aqui, todo encontro vira história.</span></div></div><div className="hero-sticker"><Sparkles size={19}/><span>Pode chegar.<br/><strong>A casa é sua.</strong></span></div><div className="hero-roundel"><Beer size={28}/><span>BOA RESENHA</span></div></Reveal></section>
        <div className="word-band" aria-label="O clima da casa"><span>Porções para dividir</span><Sparkles/><span>Bebida gelada</span><Sparkles/><span>Conversa sem pressa</span><Sparkles/><span>Seu ponto de encontro</span></div>
        <section className="container section"><SectionHeading eyebrow="Da nossa cozinha" title="Sabor bom é sabor compartilhado." text="Escolha seu favorito, puxe uma cadeira e fique à vontade." link={{href:url('cardapio.php'),label:'Ver cardápio completo'}}/><MenuItems items={data.menu} compact/></section>
        <About settings={s} compact/>
        <section className="container section"><SectionHeading eyebrow="Marque na agenda" title="Tem encontro marcado por aqui." text="Acompanhe o que vem por aí na casa." link={{href:url('eventos.php'),label:'Toda a programação'}}/><Events items={data.events.slice(0,3)} settings={s}/></section>
        <Gallery items={data.gallery} compact/>
        <Visit settings={s}/>
      </>}
      {page==='menu' && <><PageIntro eyebrow="Comida, bebida & boa companhia" title="O que vai ser hoje?" text="Confira o cardápio da casa. Tem sempre espaço para mais um à mesa."/><section className="container section page-section"><MenuItems items={data.menu}/><p className="fine-print">Preços sujeitos a atualização pela casa. Consulte a disponibilidade antes de pedir.</p></section><Visit settings={s}/></>}
      {page==='about' && <><PageIntro eyebrow="Um lugar, muitas histórias" title="Mais que um bar. Um ponto de encontro." text="A melhor parte de qualquer lugar são as pessoas que a gente encontra nele."/><About settings={s}/><Gallery items={data.gallery} compact/><Visit settings={s}/></>}
      {page==='events' && <><PageIntro eyebrow="A próxima boa história" title="Vamos marcar um encontro?" text="Nossa agenda de eventos, encontros e bons momentos."/><section className="container section page-section"><Events items={data.events} settings={s}/></section><Visit settings={s}/></>}
      {page==='event' && <EventDetail item={data.events.find(e=>e.id===data.eventId)} settings={s}/>}
      {page==='gallery' && <><PageIntro eyebrow="O nosso cantinho" title="Um bar cheio de personalidade." text="Fotos reais, detalhes da casa e um pouco do nosso jeito de receber."/><Gallery items={data.gallery}/><Visit settings={s}/></>}
      {page==='contact' && <><PageIntro eyebrow="Pode chegar" title="O próximo encontro começa aqui." text="Tire uma dúvida, combine uma visita ou simplesmente mande um oi."/><Contact settings={s}/></>}
    </main>
    <footer className="site-footer"><div className="container footer-grid"><div><a className="footer-brand" href={url('index.php')}><Beer size={26}/><span>Encontro dos Amigos</span></a><p>Um lugar para brindar,<br/>compartilhar e se sentir em casa.</p></div><div><h3>Encontre seu caminho</h3>{navigation.slice(1,5).map(([key,label,path])=><a key={key} href={url(path)}>{label}</a>)}</div><div><h3>Por aqui</h3><p>{s.endereco}<br/>{s.cidade}</p><p>{s.horario}</p></div><div><h3>Vamos conversar</h3><a href={whatsapp(s)} target="_blank" rel="noopener noreferrer"><MessageCircle size={15}/>WhatsApp</a>{safeSocial(s.instagram) && <a href={safeSocial(s.instagram)} target="_blank" rel="noopener noreferrer"><Instagram size={15}/>Instagram</a>}<a href={url('contato.php')}><ArrowUpRight size={15}/>Contato</a></div></div><div className="container footer-bottom"><span>© {new Date().getFullYear()} {s.nome}. Feito para bons encontros.</span><a href={url('admin/')}>Área administrativa<ArrowUpRight size={13}/></a></div>{s.demo_note && <div className="container demo-note">{s.demo_note}</div>}</footer>
  </div>;
}
function PageIntro({eyebrow,title,text}:{eyebrow:string;title:string;text:string}) {
  return <section className="page-intro"><div className="container"><p className="eyebrow"><span/>{eyebrow}</p><h1>{title}</h1><p className="muted">{text}</p></div><Sparkles className="intro-spark" size={60}/></section>;
}
function MenuItems({items,compact=false}:{items:Entry[];compact?:boolean}) {
  const [category,setCategory]=useState('Todos');
  const [search,setSearch]=useState('');
  const categories=['Todos',...new Set(items.map(i=>i.categoria || 'Outros'))];
  const filtered=items.filter(i=>(category==='Todos' || (i.categoria||'Outros')===category) && (i.titulo+' '+i.descricao).toLocaleLowerCase('pt-BR').includes(search.toLocaleLowerCase('pt-BR')));
  return <>{!compact && <div className="menu-tools"><div className="filter-tabs" aria-label="Categorias do cardápio">{categories.map(c=><button key={c} onClick={()=>setCategory(c)} className={c===category ? 'selected' : ''} aria-pressed={c===category}>{c}</button>)}</div><label className="search-box"><Search size={17}/><input value={search} onChange={e=>setSearch(e.target.value)} placeholder="O que você procura?" aria-label="Buscar no cardápio"/></label></div>}
    {!compact && <div className="results-heading"><TextMorph as="h2">{category}</TextMorph><span>{filtered.length} {filtered.length===1 ? 'item' : 'itens'}</span></div>}
    <div className="menu-grid">{filtered.slice(0,compact ? 4 : undefined).map((item,index)=><Reveal key={item.id} delay={index*.05}><article className="menu-card">{item.imagem ? <img className="menu-image" src={optimized(item.imagem)} alt={item.titulo} loading="lazy"/> : <div className={'menu-illustration ' + (/bebida|drink/i.test(item.categoria||'') ? 'drink' : '')}>{/bebida|drink/i.test(item.categoria||'') ? <Beer size={58} strokeWidth={1}/> : <Utensils size={58} strokeWidth={1}/>}<span>{item.categoria || 'Da casa'}</span><Sparkles className="menu-spark" size={22}/></div>}<div className="menu-card-body"><span className="category-tag">{item.categoria || 'Da casa'}</span><div className="menu-title-price"><h3>{item.titulo}</h3><strong>{money(item.preco)}</strong></div><p>{item.descricao}</p></div></article></Reveal>)}</div>
    {!filtered.length && <div className="empty-state"><Utensils size={36}/><h3>Nada por aqui ainda.</h3><p>{search ? 'Tente outra busca ou categoria.' : 'O cardápio está sendo preparado. Fale com a casa para saber as opções.'}</p></div>}
  </>;
}
function About({settings:s,compact=false}:{settings:Record<string,string>;compact?:boolean}) {
  return <section className="about-section"><div className="container about-grid"><Reveal className="about-pictures"><img className="about-main" src={asset('assets/uploads/bar-arte-parede.webp')} alt="Arte e quadros nas paredes do bar" loading="lazy"/><img className="about-detail" src={asset('assets/uploads/bar-placa-pingas.webp')} alt="Uma das placas bem-humoradas da decoração" loading="lazy"/><span className="about-badge">Histórias que<br/>a gente <em>divide.</em></span></Reveal><Reveal className="about-copy"><p className="eyebrow"><span/> O nosso jeito de receber</p><h2>O nosso bar.<br/><em>O seu lugar.</em></h2><p>{plainStory(s.sobre_texto)}</p><div className="about-values"><span><Utensils size={19}/>Para compartilhar</span><span><Beer size={19}/>Para brindar</span><span><Music2 size={19}/>Para ficar</span></div>{compact ? <a className="text-link" href={url('sobre.php')}>Conheça a nossa história<ArrowUpRight size={18}/></a> : <p className="about-signature">{s.sobre_titulo}</p>}</Reveal></div></section>;
}
function Events({items,settings:s}:{items:Entry[];settings:Record<string,string>}) {
  if (!items.length) return <div className="empty-state"><CalendarDays size={36}/><h3>A próxima data vem aí.</h3><p>Fale com a casa e acompanhe a programação.</p><a className="button" href={whatsapp(s)}>Chamar no WhatsApp<ArrowUpRight size={16}/></a></div>;
  return <div className="events-grid">{items.map((item,index)=><Reveal key={item.id} delay={index*.08}><article className="event-card"><div className="event-art" style={item.imagem ? {backgroundImage:'url("' + optimized(item.imagem) + '")'} : undefined}><div className="event-date"><strong>{dateLabel(item.data,{day:'2-digit'})}</strong><span>{dateLabel(item.data,{month:'short'}).replace('.','')}</span></div>{!item.imagem && <><Music2 size={66} strokeWidth={.8}/><span className="event-art-label">bons<br/><em>encontros.</em></span></>}{item.destaque && <span className="event-featured"><Sparkles size={12}/>Em destaque</span>}</div><div className="event-body"><div className="event-meta"><span><CalendarDays size={14}/>{dateLabel(item.data,{weekday:'long'})}</span><span><Clock3 size={14}/>{item.hora}</span></div><h3><a href={url('evento.php?id=' + encodeURIComponent(item.id))}>{item.titulo}</a></h3><p>{item.descricao}</p><a className="text-link" href={url('evento.php?id=' + encodeURIComponent(item.id))}>Saiba mais<ArrowUpRight size={16}/></a></div></article></Reveal>)}</div>;
}
function EventDetail({item,settings:s}:{item?:Entry;settings:Record<string,string>}) {
  if (!item) return <section className="container section empty-state"><CalendarDays size={42}/><h1>Evento não encontrado.</h1><p>Este evento pode ter saído da programação.</p><a className="button" href={url('eventos.php')}>Ver a agenda</a></section>;
  return <><PageIntro eyebrow="Encontro marcado" title={item.titulo} text={dateLabel(item.data) + ' · ' + item.hora}/><section className="container section event-detail"><div>{item.imagem && <img src={optimized(item.imagem)} alt={item.titulo}/>}<h2>Sobre o encontro</h2><p className="preserve-lines">{item.descricao}</p></div><aside className="event-info"><CalendarDays/><h3>Anote aí</h3><p>{dateLabel(item.data)}</p><p><Clock3 size={16}/>{item.hora}</p><p><MapPin size={16}/>{s.endereco}, {s.cidade}</p><a className="button amber" href={whatsapp(s,'Olá! Quero saber mais sobre o evento ' + item.titulo + '.')} target="_blank" rel="noopener noreferrer">Falar com a casa<ArrowUpRight size={16}/></a></aside></section></>;
}
function Gallery({items,compact=false}:{items:Entry[];compact?:boolean}) {
  const [selected,setSelected]=useState<number|null>(null);
  const visible=items.filter(i=>i.imagem).slice(0,compact ? 4 : undefined);
  useEffect(()=>{
    if (selected===null) return;
    const listener=(event:KeyboardEvent)=>{if(event.key==='ArrowRight')setSelected((selected+1)%visible.length);if(event.key==='ArrowLeft')setSelected((selected-1+visible.length)%visible.length);};
    window.addEventListener('keydown',listener);return()=>window.removeEventListener('keydown',listener);
  },[selected,visible.length]);
  return <section className={'container section ' + (compact ? '' : 'page-section')}><SectionHeading eyebrow="Cada detalhe, uma história" title={compact ? 'Um pouquinho de nós.' : 'Entre. Fique à vontade.'} link={compact ? {href:url('galeria.php'),label:'Explorar a galeria'} : undefined}/><div className={'gallery-grid ' + (compact ? 'compact' : '')}>{visible.map((item,index)=><Reveal key={item.id} delay={index*.04}><button className="gallery-tile" onClick={()=>setSelected(index)} aria-label={'Ampliar foto: ' + item.titulo}><img src={optimized(item.imagem)} alt={item.titulo} loading="lazy"/><span>{item.titulo}<ArrowUpRight size={17}/></span></button></Reveal>)}</div>{!visible.length && <div className="empty-state"><h3>Em breve, mais fotos por aqui.</h3></div>}
    <Modal open={selected!==null} onOpenChange={open=>{if(!open)setSelected(null);}} title={selected!==null ? visible[selected]?.titulo ?? 'Galeria' : 'Galeria'} description={selected!==null ? visible[selected]?.descricao : undefined} className="gallery-modal">{selected!==null && <><img className="lightbox-image" src={optimized(visible[selected]?.imagem)} alt={visible[selected]?.titulo}/><div className="lightbox-controls"><button className="button outline" aria-label="Foto anterior" onClick={()=>setSelected((selected-1+visible.length)%visible.length)}><ChevronLeft size={18}/>Anterior</button><span>{selected+1} / {visible.length}</span><button className="button outline" aria-label="Próxima foto" onClick={()=>setSelected((selected+1)%visible.length)}>Próxima<ChevronRight size={18}/></button></div></>}</Modal>
  </section>;
}
function Visit({settings:s}:{settings:Record<string,string>}) {
  return <section className="visit-section"><div className="container visit-grid"><div><p className="eyebrow light"><span/> A gente se vê por aqui</p><h2>Chama os amigos.<br/><em>A mesa é de vocês.</em></h2><a className="button amber" href={whatsapp(s)} target="_blank" rel="noopener noreferrer">Vamos combinar?<ArrowUpRight size={18}/></a></div><div className="visit-details"><div><MapPin/><p><strong>Nosso endereço</strong>{s.endereco}<br/>{s.cidade}</p></div><div><Clock3/><p><strong>Quando encontrar a gente</strong>{s.horario}</p></div><a className="text-link" href={url('contato.php')}>Ver todas as informações<ArrowUpRight size={16}/></a></div></div></section>;
}
function Contact({settings:s}:{settings:Record<string,string>}) {
  const [submitted,setSubmitted]=useState(false);
  const [link,setLink]=useState('');
  function submit(event:FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const data=new FormData(event.currentTarget);
    setLink(whatsapp(s,'Olá! Sou ' + data.get('nome') + '. ' + data.get('mensagem')));
    setSubmitted(true);
  }
  const maps='https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(s.endereco + ', ' + s.cidade);
  return <section className="container section contact-grid"><div className="contact-info"><p className="eyebrow"><span/> Estamos por aqui</p><h2>Fale com a casa.</h2><a href={whatsapp(s)} target="_blank" rel="noopener noreferrer"><MessageCircle/><span><strong>WhatsApp</strong>{s.whatsapp}</span><ArrowUpRight size={17}/></a><a href={'tel:' + s.telefone.replace(/\D/g,'')}><Phone/><span><strong>Telefone</strong>{s.telefone}</span></a><a href={'mailto:' + s.email}><Mail/><span><strong>E-mail</strong>{s.email}</span></a><a href={maps} target="_blank" rel="noopener noreferrer"><MapPin/><span><strong>Venha nos visitar</strong>{s.endereco}<br/>{s.cidade}</span><ArrowUpRight size={17}/></a><div><Clock3/><span><strong>Horário da casa</strong>{s.horario}</span></div></div><div className="contact-form-card"><p className="eyebrow"><span/> Um oi já é um bom começo</p><h2>Vamos conversar?</h2><p className="muted">Prepare sua mensagem e envie pelo WhatsApp. Você confirma o envio no aplicativo.</p>{submitted ? <div className="contact-ready" role="status"><MessageCircle size={40}/><h3>Sua mensagem está pronta.</h3><a className="button amber" href={link} target="_blank" rel="noopener noreferrer">Abrir no WhatsApp<ArrowUpRight size={18}/></a><button className="text-link" onClick={()=>setSubmitted(false)}>Editar mensagem</button></div> : <form onSubmit={submit}><label className="field">Seu nome<input name="nome" autoComplete="name" required maxLength={100} placeholder="Como podemos chamar você?"/></label><label className="field">Sua mensagem<textarea name="mensagem" required maxLength={2000} rows={5} placeholder="Conte pra gente o que você tem em mente…"/></label><ShimmerButton type="submit" className="button amber">Preparar mensagem<ArrowUpRight size={18}/></ShimmerButton><p className="fine-print">Esta página não armazena sua mensagem.</p></form>}</div></section>;
}
