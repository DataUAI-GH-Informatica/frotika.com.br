# ADR-010 — Anexos polimórficos em tabela única

- **Status:** aceito
- **Data:** 2026-07-30
- **Relaciona-se com:** seções 5.4 (Abastecimentos), 5.6 (Financeiro), 5.8 (Suporte) e 7 (CT-e) do blueprint · ADR-004 (importação de CT-e)

## Contexto

Três tabelas nasceram com uma coluna de anexo — `fuelings.receipt_path`, `maintenances.attachment_path`, `financial_entries.attachment_path` — e nenhuma delas chegou a ser preenchida por uma tela. Cada coluna comporta um arquivo só, não guarda nome original nem quem enviou, e obriga a repetir upload, validação, download autenticado e exclusão a cada módulo novo.

O que a transportadora precisa guardar é justamente o contrário disso: o cupom do posto **e** a foto do painel no mesmo abastecimento, o canhoto assinado **e** a fatura no mesmo CT-e. Na conciliação, o papel é a prova de que o número está certo.

A tabela `attachments` já estava prevista na seção 5.8 do blueprint. Este ADR registra como ela foi implementada.

## Decisão

1. **Uma tabela polimórfica, não uma coluna por módulo.** `attachments` com `attachable_type`/`attachable_id`, `disk`, `path`, `original_name`, `mime`, `size_bytes`, `uploaded_by`, sob `BelongsToCompany`. Anexar em manutenção ou em lançamento financeiro depois não exige migration nem controller novo.

2. **`AttachableType` é o registro único do que aceita anexo.** O enum diz a classe do model, o segmento pt-BR da URL e da pasta no disco, e o rótulo. Nada disso vira string espalhada por controller. Adicionar um módulo é um case novo.

3. **`attachable_type` guarda o FQCN**, como já faz `financial_entries.sourceable_type`. Duas convenções de morph na mesma base custariam mais do que economizariam.

4. **Disco privado, download por rota autenticada.** Anexo é documento fiscal de cliente: nunca URL pública, nunca `storage:link`. `GET /anexos/{id}` confere a permissão e faz stream.

5. **Caminho isolado por grupo, nome no disco é UUID:** `grupos/{group_uuid}/anexos/{tipo}/{id}/{uuid}.{ext}`. O nome original fica só no banco. Motivo: nome de arquivo vindo do usuário traz acento, barra e caminho relativo; o UUID elimina colisão e travessia de diretório de uma vez, e o nome amigável volta no `Content-Disposition` do download.

6. **Exclusão é definitiva, sem soft delete.** Remover o anexo apaga o registro e o arquivo. Um registro soft-deleted apontando para arquivo inexistente é pior que a ausência do registro.

7. **Anexo não tem permissão própria: delega ao dono, pela habilidade `attach`.** Quem enxerga o abastecimento baixa o cupom dele; quem pode mexer no CT-e remove o documento anexado. A habilidade é explícita (`attach`) e não reaproveita `update`, porque `CteDocumentPolicy` não tem `update` — CT-e é importado, não editado.

8. **O XML do CT-e continua fora da tabela.** Ele é a fonte fiscal do documento, tem hash, idempotência e rota própria (`cte.xml`), e é escrito pelo importador, não pelo usuário. O painel de anexos é para o papel que a pessoa envia.

9. **As três colunas legadas ficam onde estão, sem uso.** Removê-las agora misturaria duas mudanças na mesma entrega. Saem em migration própria depois que os anexos estiverem em uso.

10. **Upload por formulário multipart, não Livewire.** O resto da interface é Blade com controller invocável; introduzir `WithFileUploads` só aqui traria uma segunda maneira de enviar arquivo sem resolver nenhum problema que o form clássico não resolva.

## Consequências

- O limite (10 MB) e a lista de extensões (PDF, JPG, PNG, WEBP, XML) vivem em `config/attachments.php` e são lidos por `AttachmentRules` — o `FormRequest`, a Action e o texto de ajuda do formulário nunca divergem.
- A validação acontece duas vezes de propósito: no `FormRequest`, para dar mensagem em pt-BR no campo certo, e na Action, porque ela também é chamada de fora de um request.
- Não há antivírus nem inspeção de conteúdo além da checagem de mime do Laravel. Enquanto o arquivo só sai por download autenticado e nunca é servido inline pelo domínio da aplicação, o risco fica contido.
- Excluir um abastecimento faz soft delete e o anexo continua no disco, órfão para o usuário mas íntegro para auditoria. Uma limpeza dos anexos de registros excluídos em definitivo fica como incremento.
- Ainda não há renomear, reordenar, descrever nem pré-visualizar anexo. A lista é cronológica, do mais recente para o mais antigo.
