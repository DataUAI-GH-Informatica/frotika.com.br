# ADR-009 — Importação de abastecimentos por planilha XLSX

- **Status:** aceito
- **Data:** 2026-07-29
- **Relaciona-se com:** seções 2.1 (dependências), 5.4 (Abastecimentos) e 6.3 (EntrySynchronizer) do blueprint · ADR-004 (parceiros) · ADR-007 (filas e websocket)

## Contexto

A transportadora já controla abastecimento em planilha antes de comprar o Frotika, e o cartão de frota entrega o extrato do mês em Excel. Lançar de novo, um por um, é o que trava a adoção: sem abastecimento lançado não há km/l nem custo de combustível, e sem esses dois o DRE Veicular não responde a pergunta que o produto existe para responder.

O padrão de importação em lote já estava resolvido para CT-e (upload → lote → job → resultado por item → aviso por websocket). O que faltava era o equivalente para uma fonte tabular, onde o arquivo traz N registros em vez de um.

## Decisão

1. **XLSX apenas, via `phpoffice/phpspreadsheet` ^5.9.** O plano previa `maatwebsite/excel`, mas ele é um wrapper sobre o PhpSpreadsheet e, na versão 3.1, fixa o PhpSpreadsheet no ramo 1.x, já em fim de vida. O uso aqui é ler linha a linha e gerar a planilha modelo — o wrapper não agrega. CSV ficou fora: o encoding e o separador decimal de planilha brasileira geram mais suporte do que economizam.

2. **Cabeçalho semântico casado por nome, não por posição.** A primeira linha nomeia as colunas (`placa_veiculo`, `data_hora_abastecimento`, ...). Reordenar colunas, apagar uma opcional ou manter colunas próprias do cliente não quebra a importação. Faltando uma das sete obrigatórias, o arquivo é recusado no próprio formulário.

3. **Valor de enum ou rótulo pt-BR, indiferente.** `diesel_s10` e `Diesel S10` resolvem para o mesmo caso; a comparação descarta acento e separador. Quem preenche à mão digita o rótulo, e recusar isso seria criar um erro sem motivo.

4. **Idempotência em duas camadas.** Com `codigo_abastecimento` preenchido, ele é a chave: `unique(company_id, import_code)` garante no banco, não só na aplicação, que dois envios simultâneos não gravam o mesmo código duas vezes. Sem código, a assinatura placa + data/hora + litros + valor total. Duplicidade é linha **ignorada**, nunca falha — reenviar a mesma planilha é operação normal, não erro do usuário.

   A busca por código inclui o registro excluído: o índice único não distingue soft delete, então recusar na aplicação é melhor que estourar violação de constraint no insert.

5. **Um job por planilha, não um por linha.** No CT-e cada arquivo é independente e os jobs correm em paralelo. Aqui a ordem importa: o odômetro de uma linha valida a seguinte e o km/l sai do intervalo entre dois abastecimentos consecutivos. Processar fora de ordem produziria consumo errado. O teto de 1000 linhas por arquivo é o que mantém memória e tempo de fila previsíveis com um job só.

6. **A persistência passa por `CreateFueling`, sem atalho.** A importação monta o `FuelingData` e chama a mesma Action da tela. É o que garante que o abastecimento importado tenha a guarda de odômetro, o recálculo de consumo, o observer e o `financial_entry` — regra 7 do AGENTS.md continua valendo, a importação não fala com `financial_entries`.

7. **`import_code` é atributo só de criação.** Ele fica fora de `FuelingData::toAttributes()` de propósito, porque a Action de edição reusa esse mesmo array: incluí-lo faria uma edição pela tela apagar a chave de idempotência de um abastecimento importado.

8. **Odômetro regressivo falha a linha.** A tela tem um "confirmar correção"; a planilha não. Aceitar km para trás em silêncio, em lote, estragaria o km/l de todo o histórico do veículo. A mensagem manda corrigir a planilha ou lançar pela tela.

9. **Posto cadastrado sozinho, na política do ADR-004.** Com CNPJ, deduplica por documento e cria o parceiro (`kind = gas_station`) quando não existe; sem razão social nem fantasia, o nome fica `Posto {CNPJ formatado}`. Sem CNPJ, casa por nome conferindo que cidade e UF não contradizem o cadastro. Enriquece campo vazio, nunca sobrescreve dado editado à mão e só promove `kind` que ainda era `other`.

   Quando a planilha traz **apenas cidade e UF**, sem CNPJ nem nome, não há parceiro para criar — os dados ficam no texto livre do próprio abastecimento (`station_city`, `station_state`) e a linha importa. Falhar aqui, como se cogitou, recusaria uma linha que tem tudo o que o DRE precisa: o posto é opcional.

10. **Data lida como relógio de parede, sem conversão de fuso.** O formulário da tela grava `fueled_at` exatamente como digitado, e a aplicação roda em UTC. Converter de `America/Sao_Paulo` na importação deixaria o abastecimento importado 3 horas fora do lançado à mão, no mesmo relatório.

11. **A planilha é lida duas vezes.** No upload, para devolver erro de cabeçalho, de limite ou de arquivo vazio no próprio formulário; no job, para importar. Descobrir que a planilha estava errada só na tela de resultado, minutos depois, seria pior que o custo de reparsear mil linhas.

12. **A planilha modelo tem uma segunda aba com as instruções.** Se o texto de ajuda ficasse embaixo do cabeçalho, a importação leria a ajuda como um abastecimento. O leitor sempre usa a primeira aba, não a que ficou selecionada quando o arquivo foi salvo.

## Consequências

- Veículo e motorista **não** são criados automaticamente. Placa sem cadastro falha a linha com mensagem objetiva. Diferente do CT-e (ADR-004, `ProvisionVehicleByPlate`), aqui não há documento fiscal atrás do dado: um erro de digitação na placa criaria um veículo fantasma no DRE.
- O extrato de cartão de frota ainda não é importado no formato do emissor — o cliente precisa passar para o layout do Frotika. Um mapeamento por operadora fica como incremento.
- Não há edição em massa depois da importação: linha que falhou é corrigida na planilha e reenviada, e o que já entrou é ignorado.
- `Brl::normalizeDecimal()` passou a ser a fonte única da normalização de decimal pt-BR, usada pelo `FormRequest` da tela e pelo parser da planilha. A ambiguidade do ponto sem vírgula (`1.200`) continua resolvida como decimal, igual à tela — menos no odômetro, que é inteiro e por isso trata ponto como separador de milhar.
