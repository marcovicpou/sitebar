import type { SiteData } from '../types';
export const initial = JSON.parse(document.querySelector('#app-data')!.textContent!) as SiteData;
export const url = (path = '') => initial.base + '/' + path.replace(/^\//,'');
export const asset = (path?: string) => {
  if (!path || !/^assets\/(uploads|img)\/[\w.-]+\.(png|jpe?g|webp|svg)$/i.test(path)) return '';
  return url(path);
};
export const whatsapp = (settings: Record<string,string>, message?: string) => {
  const phone = settings.whatsapp?.replace(/\D/g,'');
  return phone ? 'https://wa.me/' + phone + (message ? '?text=' + encodeURIComponent(message) : '') : url('contato.php');
};
export const money = (value?: string) => {
  const amount = Number((value ?? '').replace(',','.'));
  return value && Number.isFinite(amount) ? amount.toLocaleString('pt-BR',{style:'currency',currency:'BRL'}) : 'Consulte a casa';
};
export const dateLabel = (date?: string, options: Intl.DateTimeFormatOptions = { day:'numeric', month:'long' }) =>
  date ? new Date(date + 'T12:00:00').toLocaleDateString('pt-BR',options) : 'Data a confirmar';
export async function api<T>(action: string, payload?: unknown, csrf?: string): Promise<T> {
  const headers: Record<string,string> = {};
  let body: BodyInit | undefined;
  if (payload instanceof FormData) body = payload;
  else if (payload !== undefined) { headers['Content-Type']='application/json'; body=JSON.stringify(payload); }
  if (csrf) headers['X-CSRF-Token']=csrf;
  const response = await fetch(url('api/index.php?action=' + action),{ method:payload === undefined ? 'GET' : 'POST', credentials:'same-origin', headers, body });
  let data: { ok?: boolean; error?: string };
  try { data = await response.json(); } catch { throw new Error('O servidor não respondeu. Tente novamente.'); }
  if (!response.ok || !data.ok) throw new Error(data.error ?? 'Não foi possível concluir esta ação.');
  return data as T;
}
