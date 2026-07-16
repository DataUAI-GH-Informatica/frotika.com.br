---
name: 'CT-e'
description: 'Parser e importação de CT-e — armadilhas conhecidas'
applyTo: 'app/Domain/Trips/**,tests/Fixtures/Cte/**,tests/Feature/Cte/**'
---

# CT-e no Frotika

É por aqui que a receita entra no sistema. Bug aqui = receita errada no DRE.

**Antes de qualquer alteração:** leia a seção 7 de [docs/frotika-blueprint.md](../../docs/frotika-blueprint.md) e valide contra os XMLs reais em `tests/Fixtures/Cte/`. O blueprint é a hipótese; o XSD da SEFAZ e os arquivos reais são a verdade.

## Armadilhas — todas já custaram tempo

1. **Namespace obrigatório.** `SimpleXML` sem `registerXPathNamespace('cte', 'http://www.portalfiscal.inf.br/cte')` retorna vazio em silêncio, sem erro.
2. **`tpMed` é texto livre.** Aparece como `PESO BRUTO`, `Peso Bruto`, `PESO BASE DE CALCULO`, `KG`. Normalize (upper + sem acento) e case por *contains* `PESO`, preferindo `BRUTO`. Nunca assuma posição fixa no `infQ`.
3. **O grupo de ICMS varia.** `ICMS00`, `ICMS20`, `ICMS45`, `ICMS60`, `ICMS90`, `ICMSOutraUF`, `ICMSSN`. Itere os filhos de `imp/ICMS` e pegue o primeiro `vICMS`. Em `ICMSSN` não existe `vICMS` → 0.
4. **`vRec ≠ vTPrest`.** A receita do DRE é **`vTPrest`**. `vRec` é registrado só para conferência.
5. **`toma4` vs `toma3`.** Com `toma = 4` os dados do tomador vêm em `ide/toma4`. Com 0–3, é preciso resolver a referência para `rem`/`exped`/`receb`/`dest`.
6. **`tpCTe`.** 0 normal, 1 complemento (receita adicional), 2 anulação (cancela o referenciado, **não** é receita), 3 substituto (cancela o referenciado).
7. **Encoding.** Alguns ERPs geram ISO-8859-1 sem declarar. Detecte e converta antes do parse.
8. **Valores usam ponto decimal.** `(int) round((float) $v * 100)` ou `Money::of($v, 'BRL')`. Nunca `str_replace(',', '.')`.
9. **Unique é `(company_id, access_key)`**, não `access_key` sozinho. A mesma chave pode ser importada por duas empresas do grupo.
10. **Se `emit/CNPJ` ≠ CNPJ da empresa**, ela é tomadora — o CT-e é despesa, não receita. Bloqueie com mensagem clara. Não adivinhe.

## Import

Assíncrono e idempotente: `updateOrCreate` por `(company_id, access_key)`. Reimportar atualiza, nunca duplica.

## Testes

Toda alteração no parser exige fixture nova em `tests/Fixtures/Cte/` e asserção campo a campo no `CteData`. Ver a lista mínima na seção 7.6 do blueprint.
