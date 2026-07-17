---
description: 'Revisa uma tela contra o checklist de qualidade visual do Frotika'
---

Revise a interface indicada contra a skill [frotika-ui](../skills/frotika-ui/SKILL.md).

1. Carregue a skill e o [checklist de qualidade](../skills/frotika-ui/reference/qualidade.md).
2. Suba a aplicação (`composer dev`) e abra a tela no navegador.
3. **Tire screenshot em 1440×900 (o uso principal) e 390×844.** Olhe as duas. Uma imagem vale mais que mil tokens.
4. **Conte as linhas visíveis sem rolar em 1440×900.** Se for listagem e der menos de 16, é bloqueante — meça, não estime.
5. Aplique o **teste dos 3 segundos**: apague mentalmente as palavras. Sobrou o admin de um e-commerce? Se sim, o problema é direção, não detalhe — diga isso primeiro.
6. Percorra o checklist item por item, com atenção redobrada à seção **Desktop** — é onde o produto vive.

Reporte por achado: **arquivo:linha** · **regra violada** · **o que fazer**.

Separe em:
- **Direção errada** — a tela não é um painel de instrumentos. Precisa refazer, não ajustar.
- **Bloqueante** — viola regra do checklist.
- **Sugestão.**

Não corrija nada nesta passada. Só reporte.
