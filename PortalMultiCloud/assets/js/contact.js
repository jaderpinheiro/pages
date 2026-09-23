// Formulário de contato (EmailJS — mesma conta/serviço/template do site atual)
// e ferramentas WebMCP que expõem as ações da página a agentes de IA.
import { $, $$ } from './env.js';

const EMAILJS = { publicKey: 'BK4qGaG0Oyju3Auok', service: 'office365_service', template: 'template_9ypsjlq' };
const WHATSAPP = 'https://wa.me/556231420818?text=Gostaria%20de%20falar%20com%20o%20suporte%20MultiCloud.';
const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

export function initContact() {
  const form = $('#contact-form');
  if (!form) return;
  const status = $('#formStatus');
  const btn = $('button[type="submit"]', form);
  const label = $('.btn__label', btn);
  let emailjsReady = false;

  const setStatus = (msg, kind = '') => {
    status.textContent = msg;
    status.className = `form__status${kind ? ` is-${kind}` : ''}`;
  };

  // "Solicitar proposta" pré-seleciona a solução
  document.addEventListener('click', (e) => {
    const a = e.target.closest('[data-solution]');
    if (a) form.elements.solution.value = a.dataset.solution;
  });

  $$('input, textarea', form).forEach((el) =>
    el.addEventListener('input', () => el.closest('.field').classList.remove('is-invalid')));

  function validate() {
    let first = null;
    const rules = {
      from_name: (v) => v.length >= 2,
      from_email: (v) => EMAIL_RE.test(v),
      message: (v) => v.length >= 5,
    };
    for (const [name, ok] of Object.entries(rules)) {
      const el = form.elements[name];
      const valid = ok(el.value.trim());
      el.closest('.field').classList.toggle('is-invalid', !valid);
      el.setAttribute('aria-invalid', String(!valid));
      if (!valid && !first) first = el;
    }
    if (first) first.focus();
    return !first;
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!validate()) {
      setStatus('Preencha nome, um e-mail válido e sua mensagem.', 'error');
      return;
    }
    if (!window.emailjs) {
      setStatus('Serviço de envio indisponível no momento. Fale conosco pelo WhatsApp (62) 3142-0818 ou comercial@multicloud.com.br.', 'error');
      return;
    }
    btn.disabled = true;
    label.textContent = 'Enviando...';
    setStatus('');
    try {
      if (!emailjsReady) { window.emailjs.init({ publicKey: EMAILJS.publicKey }); emailjsReady = true; }
      await window.emailjs.sendForm(EMAILJS.service, EMAILJS.template, form);
      setStatus('Mensagem enviada com sucesso! Nossa equipe responde em até 2 horas úteis.', 'ok');
      label.textContent = 'Mensagem enviada';
      form.reset();
      window.gtag?.('event', 'generate_lead', { method: 'formulario' });
    } catch {
      setStatus('Não foi possível enviar agora. Tente novamente ou fale pelo WhatsApp (62) 3142-0818.', 'error');
      label.textContent = 'Enviar Mensagem';
    } finally {
      btn.disabled = false;
      setTimeout(() => { label.textContent = 'Enviar Mensagem'; }, 5000);
    }
  });

  // Conversões de WhatsApp (mesmo evento para todos os botões)
  document.addEventListener('click', (e) => {
    if (e.target.closest('a[href^="https://wa.me"]')) window.gtag?.('event', 'contact', { method: 'whatsapp' });
  });

  registerWebMCP(form);
}

// WebMCP — preservado do site atual (no-op em navegadores sem suporte)
function registerWebMCP(form) {
  const mc = document.modelContext;
  if (!mc?.registerTool) return;
  mc.registerTool({
    name: 'get_company_info',
    description: 'Retorna informações institucionais da MultiCloud: tempo de mercado, diferenciais e atuação.',
    inputSchema: { type: 'object', properties: {} },
    async execute() {
      const text = $('.about__text')?.textContent.trim()
        || 'MultiCloud - Além da Nuvem. 23 anos de mercado em Computação na Cloud, Segurança e Governança de TI.';
      return { content: [{ type: 'text', text }] };
    },
  });
  mc.registerTool({
    name: 'list_solutions',
    description: 'Lista as soluções da MultiCloud (Nuvem Privada, Nuvem Pública, Segurança, Governança de TI) com suas principais características.',
    inputSchema: { type: 'object', properties: {} },
    async execute() {
      const solutions = $$('#solutions .sol').map((card) => ({
        name: $('.sol__title', card)?.textContent.trim(),
        description: $('.sol__desc', card)?.textContent.trim(),
        features: $$('.sol__list li', card).map((li) => li.textContent.trim()),
      }));
      return { content: [{ type: 'text', text: JSON.stringify(solutions) }] };
    },
  });
  mc.registerTool({
    name: 'contact_sales',
    description: 'Preenche e envia o formulário de contato da MultiCloud para solicitar uma proposta comercial.',
    inputSchema: {
      type: 'object',
      properties: {
        name: { type: 'string', description: 'Nome completo' },
        email: { type: 'string', description: 'E-mail corporativo' },
        company: { type: 'string', description: 'Nome da empresa' },
        solution: { type: 'string', description: 'Solução de interesse', enum: ['Nuvem Privada', 'Nuvem Pública', 'Segurança', 'Governança de TI', 'Todas as soluções'] },
        message: { type: 'string', description: 'Mensagem descrevendo a necessidade' },
      },
      required: ['name', 'email', 'message'],
    },
    async execute({ name, email, company, solution, message }) {
      form.elements.from_name.value = name;
      form.elements.from_email.value = email;
      if (company) form.elements.company.value = company;
      if (solution) form.elements.solution.value = solution;
      form.elements.message.value = message;
      form.requestSubmit();
      return { content: [{ type: 'text', text: 'Formulário enviado. Nossa equipe responde em até 2 horas úteis.' }] };
    },
  });
  mc.registerTool({
    name: 'get_whatsapp_contact',
    description: 'Retorna o link direto do WhatsApp para falar com um especialista da MultiCloud.',
    inputSchema: { type: 'object', properties: {} },
    async execute() { return { content: [{ type: 'text', text: WHATSAPP }] }; },
  });
}
