# Documento de Requisitos Funcionais

## Colégio Pense — Sistema NPS

**Código:** DRF-001  
**Versão:** 1.0  
**Data:** 2026-04-22  
**Status:** Aprovado  

---

## 1. Introdução

### 1.1 Propósito

Este documento consolida os requisitos funcionais e não funcionais do sistema de pesquisas NPS do Colégio Pense, validados na versão v3 (final), servindo de base para o **Documento Técnico de Arquitetura (DTA)** e para a implementação em fases.

### 1.2 Escopo

**Dentro do escopo (v1):**

- Medir satisfação de alunos e responsáveis via pesquisas NPS segmentadas.
- Painel administrativo (Filament 5) com multitenancy por unidade escolar.
- Cadastros de segmentos, disciplinas, professores, alunos e matrículas.
- Template único de pesquisa com nove seções de avaliação.
- Lotes de pesquisa com período, status, link compartilhável e respostas por matrícula.
- Identificação do respondente por matrícula (sem login de aluno/responsável).
- Dashboard com métricas NPS por segmento, disciplina, professor e período.
- Carga inicial de alunos via importação CSV.

**Fora do escopo (v1):**

- Disparo automático WhatsApp/SMS
- Exportação PDF/Excel
- API REST pública
- Login de aluno/responsável
- Notificações por email

### 1.3 Definições e Acrônimos

| Termo | Definição |
|-------|-----------|
| **NPS** | Net Promoter Score — métrica de satisfação conforme regras RN02 e RN03. |
| **NSA** | Não se aplica — opção de resposta excluída do denominador do NPS (RN08). |
| **EI** | Educação Infantil. |
| **EF1** | Ensino Fundamental I. |
| **EF2** | Ensino Fundamental II. |
| **EM** | Ensino Médio. |
| **Tenant** | Unidade escolar (`Unit`); escopo de dados e usuários do painel. |
| **Lote** | `SurveyBatch` — agrupamento de envio com período e link para respostas. |
| **Matrícula** | Identificador do aluno usado na entrada pública; resolve enrollment do ano. |

---

## 2. Descrição Geral

### 2.1 Perspectiva do Produto

A plataforma apoia o Colégio Pense na coleta e análise de feedback institucional, alinhada a **dois** tipos de NPS: por pergunta (escala 1–5) e NPS geral (0–10) na seção final, com segmentação por unidade, série, disciplina e professor.

### 2.2 Funções do Produto (resumo)

- Gestão de unidades, usuários internos (Admin/Operador) e multitenancy.
- Cadastros de domínio acadêmico (segmentos, disciplinas, professores vinculados a segmento/unidade, alunos, matrículas por ano).
- Definição do template de pesquisa (seções e perguntas) compartilhado entre unidades.
- Criação e controle de lotes de pesquisa e coleta pública de respostas.
- Cálculo e visualização de indicadores NPS com filtros.

### 2.3 Usuários e Características

| Perfil | Descrição | Necessidades principais |
|--------|-----------|-------------------------|
| **Administrador** | Gestão plena do tenant | Usuários, lotes, cadastros, reabertura de lotes. |
| **Operador** | Operação do dia a dia | Criar/ativar lotes, links, acompanhar respostas. |
| **Aluno (EF2/EM)** | Respondente anônimo vía matrícula | Responder escala; sem comentário por pergunta; texto na seção final. |
| **Responsável (EI/EF1)** | Respondente anônimo vía matrícula | Idem, em nome do vínculo da matrícula. |

*Obs.: não há autenticação de aluno/responsável; a identificação é por matrícula no fluxo público.*

### 2.4 Restrições

- Uma resposta por matrícula por lote (RN05).
- Lote só aceita respostas se estiver **Active** e dentro do período (RN04).
- Template de pesquisa **único** para todas as unidades (Decisão 9).
- Professores avaliados limitados ao vínculo segmento + unidade do respondente (RN07).
- Perguntas de professores: EI/EF1 com conjunto fixo por segmento; EF2/EM por disciplina (Seção 1).

### 2.5 Dependências

- Laravel 13, PHP 8.4, Filament 5, Livewire 4, conforme projeto.
- Banco de dados e ambiente (ex.: Docker) definidos no repositório.
- Nenhuma integração externa obrigatória para a v1 (sem API pública, sem disparo WhatsApp).

---

## 3. Requisitos Funcionais

Cada requisito referencia, quando aplicável, as regras de negócio (RNxx) da seção 3.1.

### 3.1 Regras de negócio (catálogo)

| # | Regra |
|---|-------|
| **RN01** | EI/EF1 → Responsável responde; EF2/EM → Aluno responde. |
| **RN02** | NPS por pergunta (1–5): Promotores = 4, 5; Detratores = 1, 2, 3; NPS = (P − D) / T × 100. |
| **RN03** | NPS geral (0–10): Promotores = 9, 10; Neutros = 7, 8; Detratores = 0–6. |
| **RN04** | Lote aceita respostas quando **Active** e dentro do período. |
| **RN05** | Uma resposta por matrícula por lote. |
| **RN06** | Matrícula resolve enrollment do **ano atual** → unidade, segmento e respondente. |
| **RN07** | Professores avaliados = vinculados ao segmento + unidade do respondente. |
| **RN08** | NSA não entra no total do NPS. |

### RF001 — Multitenancy e unidades

**Prioridade:** Alta  
**Descrição:** O sistema deve suportar **duas** unidades escolares como tenants distintos, utilizando multitenancy nativo do Filament 5 (`Unit` como tenant).  
**Regras de negócio:** alinhado às Decisões 4 e 9.  
**Critérios de aceite:**

- [ ] Cada unidade isola `User` e recursos escopados ao tenant.
- [ ] Dois tenants configuráveis e utilizáveis no painel.
- [ ] Navegação e dados respeitam o tenant selecionado.

### RF002 — Usuários internos (Admin/Operador)

**Prioridade:** Alta  
**Descrição:** O sistema deve permitir cadastro e gestão de usuários com papéis Admin e Operador no escopo do tenant.  
**Critérios de aceite:**

- [ ] Autenticação no painel Filament.
- [ ] Perfil de acesso distingue Admin e Operador conforme regras de produto a definir no DTA (mínimo: gestão de lotes e cadastros).

### RF003 — Identificação por matrícula (fluxo público)

**Prioridade:** Alta  
**Descrição:** O respondente informa a **matrícula**; o sistema resolve nome e vínculo (responsável vs aluno) conforme segmento.  
**Regras de negócio:** RN01, RN06.  
**Critérios de aceite:**

- [ ] EI/EF1: exibe/usa contexto de responsável conforme regra.
- [ ] EF2/EM: contexto de aluno conforme regra.
- [ ] Matrícula sem enrollment no ano corrente: comportamento de erro claro (mensagem a definir no DTA).

### RF004 — Cadastros: segmentos, disciplinas, professores, alunos e matrículas

**Prioridade:** Alta  
**Descrição:** Cadastro de **16 segmentos** (seeder), disciplinas, professores com vínculo a segmento e unidade, aluno global e matrícula anual.  
**Regras de negócio:** RN07. Decisões 6, 8, 10.  
**Critérios de aceite:**

- [ ] 16 segmentos disponíveis via seeder.
- [ ] `Student` global; `Enrollment` liga aluno, unidade e segmento por ano letivo.
- [ ] `SegmentTeacher` define professores a avaliar por segmento/tenant.
- [ ] Disciplinas utilizadas na Seção 1 para EF2/EM.

### RF005 — Importação CSV de alunos

**Prioridade:** Alta  
**Descrição:** Carga inicial (e evolução) de alunos via arquivo CSV, conforme Decisão 11.  
**Critérios de aceite:**

- [ ] Fluxo de importação com validação e tratamento de erros.
- [ ] Documentação mínima de colunas obrigatórias no DTA/operacional.

### RF006 — Template de pesquisa (Survey, seções, perguntas)

**Prioridade:** Alta  
**Descrição:** Modelagem de **um** template de pesquisa com **9 seções**; Seção 1 varia por grupo de segmento; demais seções fixas no template.  
**Regras de negócio:** Decisões 3, 5, 7, 9, 12.  
**Critérios de aceite:**

- [ ] Nove seções com quantidade de itens e escalas conforme tabela “Seções da Pesquisa (resumo)” neste documento.
- [ ] Escala 1–5 + NSA nas seções aplicáveis; NPS 0–10 e texto na Seção 9.
- [ ] Sem comentário por pergunta; campos de texto livre **apenas** na seção final (3 campos de texto além do NPS clássico, conforme tabela).
- [ ] Seeder com template padrão alinhado ao resumo de seções.

### RF007 — Lotes, período, status e link

**Prioridade:** Alta  
**Descrição:** `SurveyBatch` por unidade e template, com janela de resposta, transições de status e link compartilhável.  
**Regras de negócio:** RN04. Ver também “Status e Transições (SurveyBatch)”.  
**Critérios de aceite:**

- [ ] Criação em Draft; ativação para Active (gera link); encerramento para Closed.
- [ ] Reabertura de Closed → Active permitida a Admin.
- [ ] Fechamento automático por scheduler quando `ends_at` ultrapassado.
- [ ] Fora do período ou lote inativo: não aceitar novas respostas.

### RF008 — Respostas: JSON, uma por matrícula/lote, conclusão

**Prioridade:** Alta  
**Descrição:** Armazenar respostas em coluna JSON; flag `is_completed` por matrícula e lote; respeitar RN05.  
**Regras de negócio:** Decisão 2; RN05.  
**Critérios de aceite:**

- [ ] No máximo um registro de resposta final por (matrícula, lote).
- [ ] `is_completed` reflete conclusão do preenchimento conforme regra de negócio.

### RF009 — Formulário público responsivo

**Prioridade:** Alta  
**Descrição:** Formulário acessível via link, sem login de aluno/responsável, em layout responsivo.  
**Critérios de aceite:**

- [ ] Roteiro de perguntas condizente com segmento e seção 1 (professores/disciplinas).
- [ ] Experiência utilizável em desktop e mobile.

### RF010 — Cálculo de NPS (duplo)

**Prioridade:** Alta  
**Descrição:** Calcular e exibir NPS por pergunta (1–5) e NPS geral (0–10), com exclusão de NSA do denominador.  
**Regras de negócio:** RN02, RN03, RN08.  
**Critérios de aceite:**

- [ ] Fórmulas e classificações (promotores/detratores/neutros) conforme RN02 e RN03.
- [ ] Respostas **NSA** excluídas do total no cálculo (RN08).

### RF011 — Dashboard e relatórios NPS (v1)

**Prioridade:** Média  
**Descrição:** Dashboard com NPS “duplo” e filtros por lote, segmento, disciplina e professor.  
**Regras de negócio:** alinhado à RN02, RN03 e visão de produto.  
**Critérios de aceite:**

- [ ] Visualizações e filtros mínimos conforme fase 5 do plano de implementação.
- [ ] Não exige exportação PDF/Excel na v1 (fora de escopo).

### RF012 — Pós-condições e auditoria mínima

**Prioridade:** Baixa  
**Descrição:** O sistema deve permitir rastrear operação de lotes e cadastros no nível esperado do painel (detalhamento no DTA: logs, `created_by`, etc.).  
**Critérios de aceite:**

- [ ] A definir no DTA sem contradizer este DRF.

---

## 4. Requisitos Não Funcionais

### RNF001 — Usabilidade

- Interface do painel em Filament; formulário público responsivo (RF009).

### RNF002 — Manutenção e extensão

- Código e camadas alinhados a `PROJECT.md` e guias em `.ai/docs/`.
- Modelos e migrações com convenções de campos (string para enums, UUID para IDs).

### RNF003 — Segurança (v1)

- Sem API REST pública.
- Acesso administrativo autenticado; link de lote não substitui autenticação do painel.
- Detalhamento (rate limit, validação de upload CSV) no DTA.

### RNF004 — Desempenho

- Estratégia de índices e consultas do dashboard a definir no DTA; meta coerente com volume escolar (turmas/unidades).

---

## 5. Status e transições (SurveyBatch)

| De | Para | Quem | Automático? |
|----|------|------|:-----------:|
| Draft | Active | Admin/Operador | Gera link |
| Active | Closed | Admin/Operador | — |
| Active | Closed | Scheduler | Se `ends_at` passou |
| Closed | Active | Admin | Reabertura |

---

## 6. Eventos de negócio

> A ser detalhado no **DTA** (ex.: fechamento de lote, conclusão de resposta). Nenhum evento obrigatório impõe alteração a este DRF além de suportar RN04 e encerramento automático.

| Evento | Trigger | Listeners (proposto) | Fila assíncrona |
|--------|---------|----------------------|:---------------:|
| *A definir no DTA* | *—* | *—* | *—* |

---

## 7. Matriz de rastreabilidade (inicial)

| Requisito | Caso de Uso (UC) | Teste (TC) | Status |
|-----------|------------------|------------|--------|
| RF001 | *Pendente* | *Pendente* | Planejado |
| RF002 | *Pendente* | *Pendente* | Planejado |
| RF003 | *Pendente* | *Pendente* | Planejado |
| RF004 | *Pendente* | *Pendente* | Planejado |
| RF005 | *Pendente* | *Pendente* | Planejado |
| RF006 | *Pendente* | *Pendente* | Planejado |
| RF007 | *Pendente* | *Pendente* | Planejado |
| RF008 | *Pendente* | *Pendente* | Planejado |
| RF009 | *Pendente* | *Pendente* | Planejado |
| RF010 | *Pendente* | *Pendente* | Planejado |
| RF011 | *Pendente* | *Pendente* | Planejado |
| RF012 | *Pendente* | *Pendente* | Planejado |

**Cruzamento Regra de negócio → RF:**

| RN | RFs relacionados |
|----|------------------|
| RN01 | RF003 |
| RN02, RN08 | RF010 |
| RN03 | RF010, RF011 |
| RN04 | RF007 |
| RN05 | RF008 |
| RN06 | RF003, RF004 |
| RN07 | RF004, RF006 |

---

## 8. Histórico de revisões

| Versão | Data | Autor | Descrição |
|--------|------|-------|-----------|
| 1.0 | 2026-04-22 | Rafael Augusto | Consolidação a partir do plano v3 (final) e formalização DRF-001. |

---

## Anexo A — Decisões validadas

| # | Decisão |
|---|---------|
| 1 | Identificação por **matrícula** → busca auto nome responsável (EI/EF1) ou aluno (EF2/EM) |
| 2 | Flag `is_completed` por matrícula/lote; respostas em **coluna JSON** |
| 3 | Sem comentário por pergunta; campos texto livre apenas na seção final |
| 4 | **2 unidades** → Model `Unit` com multitenancy nativo Filament 5 |
| 5 | **9 seções** de avaliação; Seção 1 varia por grupo de segmento |
| 6 | Segmentos **pré-cadastrados** via seeder (16 segmentos) |
| 7 | Escala **1-5 + NSA**; pergunta NPS final **0-10** |
| 8 | **Professores pré-cadastrados** — cada segmento/unidade tem professores diferentes |
| 9 | **Template único** de pesquisa para todas unidades |
| 10 | **Student global** (sem vínculo direto a Unit); Enrollment por ano vincula Student↔Unit↔Segment |
| 11 | Carga inicial de alunos via **CSV** |
| 12 | NPS **duplo**: por pergunta (1-5) + geral (0-10 seção final) |

---

## Anexo B — Modelos de domínio (visão requisito)

*Referência para o DTA; nomes e granularidade podem ser refinados pelo architect/DBA.*

| Model | Descrição | Tenant-scoped? |
|-------|-----------|:--------------:|
| **Unit** | Unidade escolar (tenant) | — |
| **User** | Admin/Operador | Sim |
| **Segment** | Série (Maternal 1 … 3ª EM) | Global |
| **Subject** | Disciplina (para EF2/EM) | Global |
| **Teacher** | Professor pré-cadastrado | Sim |
| **SegmentTeacher** | Pivot: professores a avaliar por segmento | Sim |
| **Student** | Aluno (matrícula, nome, responsável) | Global |
| **Enrollment** | Aluno↔Unit↔Segment por ano letivo | Sim |
| **Survey** | Template da pesquisa (cabeçalho) | Global |
| **SurveySection** | Seção (ex.: Professores, Coordenação) | Global |
| **SurveyQuestion** | Pergunta dentro da seção | Global |
| **SurveyBatch** | Lote (período, status, link) | Sim |
| **SurveyResponse** | Resposta por matrícula/lote (JSON, `is_completed`) | Sim |

### B.1 Relacionamentos-chave

```
Unit ──hasMany──→ User, Teacher, Enrollment, SurveyBatch
Student ──hasMany──→ Enrollment
Enrollment ──belongsTo──→ Student, Unit, Segment
Segment ──belongsToMany──→ Teacher (via SegmentTeacher)
Survey ──hasMany──→ SurveySection ──hasMany──→ SurveyQuestion
SurveyBatch ──belongsTo──→ Unit, Survey
SurveyBatch ──hasMany──→ SurveyResponse
SurveyResponse ──belongsTo──→ SurveyBatch, Enrollment
```

---

## Anexo C — Seções da pesquisa (resumo)

| # | Seção | Itens | Escala | Obs |
|---|-------|:-----:|--------|-----|
| 1 | Professores | 6 × N | 1-5+NSA | EI/EF1: profs fixos por segmento; EF2/EM: por Subject |
| 2 | Coordenação | 6 | 1-5+NSA | |
| 3 | Secretaria | 6 | 1-5+NSA | |
| 4 | Estrutura Física | 11 | 1-5+NSA | |
| 5 | Cantina | 8 | 1-5+NSA | |
| 6 | Redes Sociais | 7 | 1-5+NSA | |
| 7 | Capelania | 6 | 1-5+NSA | |
| 8 | Avaliação Institucional | 3 | 1-5+NSA | |
| 9 | NPS Final | 4 | 0-10 + texto | Pergunta NPS clássica + 3 campos texto |

---

## Anexo D — Fases de implementação

Visão de roadmap acordada; o DTA e as migrations podem ajustar a ordem técnica sem alterar o escopo deste DRF sem nova revisão.

```
Fase 1: Base (Unit, User, Tenancy)           ──┐
Fase 2: Cadastros (Segment, Subject,         │
        Teacher, Student, Enrollment)         ──┤
Fase 3: Template (Survey, Section, Question)  ──┤
Fase 4: Core (Batch, Response, Formulário)   ──┤ ← mais complexa
Fase 5: Dashboard (NPS, Gráficos)            ──┘
```

### D.1 Fase 1 — Base

**Models:** `Unit`, `User` | **Enums:** `UserRole`  
**Entregável:** Painel Filament com login, tenancy por unidade, CRUD de usuários.

### D.2 Fase 2 — Cadastros

**Models:** `Segment`, `Subject`, `Teacher`, `SegmentTeacher`, `Student`, `Enrollment`  
**Enums:** `SegmentGroup` | **Extra:** importação CSV de alunos, seeder de segmentos  
**Entregável:** CRUD de segmentos, disciplinas, professores, alunos e matrículas.

### D.3 Fase 3 — Template de pesquisa

**Models:** `Survey`, `SurveySection`, `SurveyQuestion`  
**Enums:** `QuestionType` (ex.: Scale1to5, Scale0to10, FreeText), `SectionType`  
**Entregável:** Admin configura seções e perguntas; seeder com template padrão.

### D.4 Fase 4 — Core

**Models:** `SurveyBatch`, `SurveyResponse`  
**Enums:** `SurveyBatchStatus`, `RespondentType`  
**Entregável:** Lotes com links compartilháveis, formulário público responsivo, respostas em JSON.

### D.5 Fase 5 — Dashboard

**Entregável:** Dashboard NPS duplo (1–5 e 0–10), filtros por lote, segmento, disciplina e professor.

---

## Anexo E — Próximos passos (processo)

1. Requisitos validados (este DRF).  
2. **Agente `architect`:** produzir DTA-001 (models, relacionamentos, recursos Filament, fluxos).  
3. **Agente `dba`:** revisar estrutura de dados por fase.
