# Configuração de agentes — Frotika

## Quem lê o quê

| Arquivo | Copilot no VS Code | Codex (extensão / cloud) |
| --- | --- | --- |
| `AGENTS.md` (raiz) | sim | **sim** |
| `.github/copilot-instructions.md` | sim | não |
| `.github/instructions/*.instructions.md` | sim, por `applyTo` | não |
| `.github/agents/*.agent.md` | sim | não |
| `.github/prompts/*.prompt.md` | sim | não |
| `.vscode/settings.json` | sim | não |

Por isso o **`AGENTS.md` é a fonte canônica** e o `copilot-instructions.md` é só um ponteiro para ele. Tudo que for inegociável mora no `AGENTS.md`, para chegar em qualquer agente. Os `.instructions.md` são camada extra de detalhe, aplicada por caminho de arquivo — quando o agente é o Copilot, ela entra sozinha; quando é o Codex, o conteúdo essencial já está no `AGENTS.md`.

## Manutenção

- Regra nova e inegociável → `AGENTS.md`. Mantenha o arquivo abaixo de ~150 linhas.
- Detalhe que só vale para um tipo de arquivo → `.github/instructions/`.
- O blueprint (`docs/frotika-blueprint.md`) **não** é instrução sempre ativa. É referência linkada. Instrução longa demais degrada o resultado.
- Toda regra vem com o motivo. O agente decide melhor nos casos de borda quando sabe por que a regra existe.
- Regra que o Pint ou o Larastan já garantem não precisa estar aqui.

## Diagnóstico

Se uma instrução parece não estar sendo aplicada:

- Clique com o botão direito na Chat view → **Diagnostics**. Mostra os arquivos carregados e os erros.
- Confira a seção **References** da resposta: lista quais instruções entraram.
- Para `.instructions.md`, confirme que o `applyTo` casa com o arquivo aberto.

## Comandos úteis

- `/init` — regenera instruções analisando o código já existente.
- `/create-instruction` — cria um `.instructions.md` com `applyTo` a partir de uma descrição.
- `/nova-fase`, `/auditar-tenancy` — prompts deste projeto.
- `@Planejador`, `@Revisor` — agentes deste projeto.

Referência: https://code.visualstudio.com/docs/agent-customization/custom-instructions
