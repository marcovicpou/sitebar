export type Entry = {
  id: string; titulo: string; descricao: string; imagem?: string; ativo: boolean;
  categoria?: string; preco?: string; data?: string; hora?: string; destaque?: boolean;
  created_at?: string; updated_at?: string;
};
export type SiteData = {
  base: string; page: string; admin: boolean; eventId?: string;
  settings: Record<string,string>; menu: Entry[]; events: Entry[]; gallery: Entry[];
};
export type Collection = 'menu' | 'events' | 'gallery';
export type Session = { logged: boolean; setup: boolean; username: string; csrf: string };
