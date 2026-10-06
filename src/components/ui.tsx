import * as Dialog from '@radix-ui/react-dialog';
import { motion, useReducedMotion } from 'motion/react';
import { X, ArrowUpRight } from 'lucide-react';
import { useRef, type ReactNode } from 'react';
import { cn } from '../lib/utils';
export function Reveal({ children, className, delay=0 }: {children: ReactNode; className?: string; delay?: number}) {
  const reduce=useReducedMotion();
  return <motion.div className={className} initial={reduce ? false : {opacity:0,y:24}} whileInView={{opacity:1,y:0}} viewport={{once:true,amount:0.12}} transition={{duration:.55,delay,ease:[.22,1,.36,1]}}>{children}</motion.div>;
}
export function Modal({ open, onOpenChange, title, description, children, className }: {open:boolean;onOpenChange:(open:boolean)=>void;title:string;description?:string;children:ReactNode;className?:string}) {
  const wasOpen=useRef(false);
  const trigger=useRef<HTMLElement|null>(null);
  if(open && !wasOpen.current) trigger.current=document.activeElement instanceof HTMLElement ? document.activeElement : null;
  wasOpen.current=open;
  return <Dialog.Root open={open} onOpenChange={onOpenChange}><Dialog.Portal><Dialog.Overlay className="modal-overlay"/><Dialog.Content className={cn('modal-content',className)} onCloseAutoFocus={event=>{event.preventDefault();if(trigger.current?.isConnected)trigger.current.focus();}}>
    <div className="modal-heading"><Dialog.Title>{title}</Dialog.Title><Dialog.Close className="icon-button" aria-label="Fechar"><X size={22}/></Dialog.Close></div>
    <Dialog.Description className={description ? 'muted' : 'sr-only'}>{description ?? title}</Dialog.Description>{children}
  </Dialog.Content></Dialog.Portal></Dialog.Root>;
}
export function SectionHeading({ eyebrow, title, text, link }: {eyebrow:string;title:string;text?:string;link?:{href:string;label:string}}) {
  return <div className="section-heading"><div><p className="eyebrow"><span/> {eyebrow}</p><h2>{title}</h2>{text && <p className="muted section-description">{text}</p>}</div>{link && <a href={link.href} className="text-link">{link.label}<ArrowUpRight size={18}/></a>}</div>;
}
