# Arquitetura: Sistema NPS — Colégio Pense

**Código:** DTA-001
**Versão:** 1.2
**Data:** 2026-04-22
**Status:** Rascunho
**Referência:** DRF-001

---

## Índice

1. [Contexto](#1-contexto)
2. [Requisitos](#2-requisitos)
3. [Rastreabilidade DRF → DTA](#3-rastreabilidade-drf--dta)
4. [Decisões de Design](#4-decisões-de-design)
5. [Models](#5-models)
6. [JSON Schema para Respostas](#6-json-schema-para-respostas)
7. [Enums](#7-enums)
8. [Exceções de Domínio](#8-exceções-de-domínio)
9. [Events e Listeners](#9-events-e-listeners)
10. [Camadas (DTOs, Services, Actions, Jobs)](#10-camadas)
11. [Scheduling](#11-scheduling)
12. [Performance](#12-performance)
13. [Filament Resources](#13-filament-resources)
14. [Formulário Público (Livewire)](#14-formulário-público-livewire)
15. [Segurança](#15-segurança)
16. [Factories e Seeders](#16-factories-e-seeders)
17. [Fases de Implementação](#17-fases-de-implementação)
18. [Handoff](#18-handoff)

---

## 1. Contexto

O Colégio Pense necessita de uma plataforma para medir a satisfação de alunos e responsáveis por meio de pesquisas NPS segmentadas por unidade escolar, série, disciplina e professor. A plataforma opera com **duas unidades escolares** como tenants e utiliza um **template único** de pesquisa com 9 seções de avaliação — incluindo NPS "duplo": por pergunta (escala 1–5) e NPS geral/clássico (escala 0–10).

O sistema **não** requer login de aluno/responsável; a identificação é feita por matrícula no fluxo público. Não há API REST pública, disparo de WhatsApp/SMS, exportação PDF/Excel ou notificações por email na v1.

**Stack:** Laravel 13, PHP 8.4, Filament 5, Livewire 4, MySQL 8, UUID PKs, SoftDeletes, Enum como string no banco.

---

## 2. Requisitos

### 2.1 Requisitos Funcionais (resumo do DRF-001)

| RF | Descrição | RNs |
|----|-----------|-----|
| RF001 | Multitenancy por `Unit` (2 unidades), isola Users e dados escopados | — |
| RF002 | Usuários internos com papéis Admin e Operador | — |
| RF003 | Identificação por matrícula no fluxo público; resolve enrollment do ano corrente | RN01, RN06 |
| RF004 | Cadastros: 16 segmentos (seeder), disciplinas, professores + vínculo segmento/unidade, alunos globais, matrículas anuais | RN07 |
| RF005 | Importação CSV de alunos | — |
| RF006 | Template de pesquisa: 1 survey, 9 seções, perguntas por seção; Seção 1 varia por grupo de segmento | — |
| RF007 | Lotes de pesquisa (Draft → Active → Closed), período, link compartilhável, auto-close | RN04 |
| RF008 | Respostas em JSON, `is_completed`, 1 por matrícula/lote | RN05 |
| RF009 | Formulário público responsivo | — |
| RF010 | Cálculo NPS duplo: por pergunta (1–5) e geral (0–10), NSA excluído | RN02, RN03, RN08 |
| RF011 | Dashboard NPS com filtros (lote, segmento, disciplina, professor) | — |
| RF012 | Auditoria mínima (logs, `created_by`) | — |

### 2.2 Regras de Negócio

| # | Regra |
|---|-------|
| **RN01** | EI/EF1 → Responsável responde; EF2/EM → Aluno responde. |
| **RN02** | NPS por pergunta (1–5): Promotores = 4, 5; Detratores = 1, 2, 3; NPS = (P − D) / T × 100. |
| **RN03** | NPS geral (0–10): Promotores = 9, 10; Neutros = 7, 8; Detratores = 0–6. |
| **RN04** | Lote aceita respostas quando **Active** e dentro do período (`starts_at` ≤ now ≤ `ends_at`). |
| **RN05** | Uma resposta por matrícula por lote (`unique(enrollment_id, survey_batch_id)`). Se a resposta for apenas *soft-deleted*, o par `(enrollment_id, survey_batch_id)` continua ocupando a constraint: nova submissão exige `forceDelete` da resposta anterior ou decisão explícita de negócio (ver §5.13). |
| **RN06** | Matrícula resolve enrollment do **ano atual** → unidade, segmento e respondente. |
| **RN07** | Professores avaliados = vinculados ao segmento + unidade do respondente. |
| **RN08** | Respostas NSA não entram no denominador do cálculo NPS. |

### 2.3 Requisitos Não Funcionais

| RNF | Descrição |
|-----|-----------|
| RNF001 | Interface admin em Filament 5; formulário público responsivo em Livewire 4. |
| RNF002 | Código alinhado a PROJECT.md e guias `.ai/docs/`. UUID PKs, string para enums. |
| RNF003 | Sem API REST pública. Rate limit em endpoints públicos. Validação de CSV. |
| RNF004 | Índices otimizados para queries do dashboard; cache para agregações NPS. |

---

## 3. Rastreabilidade DRF → DTA

### 3.1 RF → Artefatos DTA

| RF | Models | Enums | Services | Actions | Filament Resource | Livewire |
|----|--------|-------|----------|---------|-------------------|----------|
| RF001 | Unit, User | UserRole | — | — | UnitResource, UserResource | — |
| RF002 | User | UserRole | — | — | UserResource | — |
| RF003 | Student, Enrollment | RespondentType, SegmentGroup | EnrollmentResolverService | — | — | PublicSurveyForm |
| RF004 | Segment, Subject, Teacher, SegmentTeacher, Student, Enrollment | SegmentGroup | — | ImportStudentsCsvAction | SegmentResource, SubjectResource, TeacherResource, StudentResource, EnrollmentResource | — |
| RF005 | Student, Enrollment | — | — | ImportStudentsCsvAction | StudentResource (import action) | — |
| RF006 | Survey, SurveySection, SurveyQuestion | QuestionType, SectionType | — | — | SurveyResource | — |
| RF007 | SurveyBatch | SurveyBatchStatus | SurveyBatchLinkService | ActivateBatchAction, CloseBatchAction | SurveyBatchResource | — |
| RF008 | SurveyResponse | — | — | CompleteSurveyResponseAction | SurveyResponseResource | PublicSurveyForm |
| RF009 | — | — | — | — | — | PublicSurveyForm, SurveyIdentification |
| RF010 | SurveyResponse | — | NpsCalculationService | — | — (widget) | — |
| RF011 | — | — | NpsCalculationService | — | NpsDashboardPage + Widgets | — |
| RF012 | (todos) | — | — | — | — | — |

### 3.2 RN → Camada de Enforcement

| RN | Onde é Enforced | Como |
|----|-----------------|------|
| RN01 | EnrollmentResolverService, PublicSurveyForm | SegmentGroup do enrollment determina `RespondentType` |
| RN02 | NpsCalculationService | Fórmula aplicada sobre respostas 1–5 |
| RN03 | NpsCalculationService | Fórmula aplicada sobre respostas 0–10 |
| RN04 | CompleteSurveyResponseAction, SurveyBatchPolicy | Verifica status=Active + now entre starts_at/ends_at |
| RN05 | Migration (unique constraint) + CompleteSurveyResponseAction | `unique(enrollment_id, survey_batch_id)` + check na Action; alinhar com política de resubmissão vs `SoftDeletes` (§5.13) |
| RN06 | EnrollmentResolverService | Query: `Enrollment::where('registration_code', $code)->where('year', now()->year)` |
| RN07 | PublicSurveyForm (Livewire) | Filtra `SegmentTeacher` por segment_id + unit_id do enrollment |
| RN08 | NpsCalculationService | `WHERE value != 'nsa'` no denominador |

---

## 4. Decisões de Design

### 4.1 Normalização

**Nível aplicado:** 3NF — Terceira Forma Normal.

#### 4.1.1 Morph vs Dedicado — Análise para NPS

| Entidade | Morph? | Decisão | Justificativa |
|----------|:------:|---------|---------------|
| Unit | — | Dedicado | Entidade core, tenant, FK constraints críticas |
| User | — | Dedicado | Autenticação; multitenancy N:N via pivot `unit_user` |
| Segment | — | Dedicado | Entidade core de domínio, FK em enrollment |
| Subject | — | Dedicado | Entidade core, FK em survey_questions e segment_teacher |
| Teacher | — | Dedicado | Entidade core global; vínculo N:N com unidades via `unit_teacher` |
| SegmentTeacher | — | Dedicado | Pivot enriquecida, FK constraints para segment+teacher+unit |
| Student | — | Dedicado | Entidade core global, FK em enrollment |
| Enrollment | — | Dedicado | Entidade core, unique composto, FK constraints críticas |
| Survey | — | Dedicado | Entidade core, template global |
| SurveySection | — | Dedicado | Entidade core, FK para survey |
| SurveyQuestion | — | Dedicado | Entidade core, FK para section |
| SurveyBatch | — | Dedicado | Entidade core de operação, transições de estado |
| SurveyResponse | — | Dedicado | Entidade core, alto volume de queries (dashboard), JSON answers |

**Resultado: NENHUMA entidade morph no NPS v1.** Todas as entidades são core de domínio, com FK constraints críticas para integridade referencial, queries complexas no dashboard e estruturas que não são compartilhadas entre models distintos. Morph é indicado para entidades auxiliares genéricas (endereços, notas, anexos) que não se aplicam a este domínio.

#### 4.1.2 Entidades Compartilhadas Identificadas

Nenhuma entidade compartilhada (morph) é necessária na v1. Endereços, contatos e anexos não fazem parte do domínio NPS.

#### 4.1.3 Desnormalizações Intencionais

| Campo | Tabela | Justificativa |
|-------|--------|---------------|
| `respondent_type` | `survey_responses` | Cache do tipo de respondente (Aluno/Responsável) determinado no momento da resposta via RN01. Evita JOIN com enrollment→segment para cada cálculo. |
| `respondent_name` | `survey_responses` | Snapshot do nome do aluno/responsável no momento da resposta. O nome pode mudar no cadastro ao longo do tempo; o snapshot preserva o contexto da resposta. |
| `segment_id` | `survey_responses` | Snapshot do segmento do enrollment no momento da resposta. Evita JOIN com enrollment em queries de dashboard para agregação por segmento. |
| `unit_id` | `survey_responses` | Redundante (inferível via batch→unit), mas necessário para queries de dashboard que filtram por unidade sem JOIN no batch. |

Cada desnormalização será documentada com comentário na migration.

### 4.2 Multitenancy

**Estratégia:** Filament 5 native tenancy com `Unit` como tenant model.

- **`User` N:N com `Unit`** via pivot `unit_user` — um usuário pode atuar em várias unidades.
  - **Filament:** `User` implementa `HasTenants`; `getTenants()` retorna `$this->units`.
- **`Teacher` N:N com `Unit`** via pivot `unit_teacher` — o mesmo professor pode lecionar em mais de uma unidade; cadastro é global e o painel filtra pelo tenant corrente via pivot.
- **`SegmentTeacher` é tenant-scoped** com `unit_id` explícito na tabela (o vínculo professor↔segmento↔disciplina pertence a uma unidade concreta).
- **Models com `unit_id` direto (dados operacionais por unidade):** SegmentTeacher, Enrollment, SurveyBatch, SurveyResponse.
- **Models globais (sem coluna `unit_id`):** Segment, Subject, Student, Survey, SurveySection, SurveyQuestion, User, Teacher — estes dois últimos associam-se às unidades apenas pelos pivots `unit_user` e `unit_teacher`.

**Configuração no `AppPanelProvider`:**
- `->tenant(Unit::class)` 
- `->tenantRoutePrefix('unit')`
- Recursos Filament que listam `User` ou `Teacher` devem restringir query com `whereHas('units', …)` ao tenant ativo (ou attach automático na criação ao tenant corrente), conforme seção 13.

### 4.3 Template Único de Pesquisa

O template de pesquisa (Survey + Sections + Questions) é **global** — compartilhado entre todas as unidades. Dados operacionais por unidade usam `unit_id` em `Enrollment`, `SegmentTeacher`, `SurveyBatch` e `SurveyResponse`. `User` e `Teacher` são globais e ligam-se às unidades pelos pivots `unit_user` e `unit_teacher`.

A Seção 1 (Professores) varia conforme o grupo de segmento do respondente:
- **EI/EF1:** Conjunto fixo de professores por segmento (via SegmentTeacher).
- **EF2/EM:** Professores avaliados por disciplina (Subject).

Essa variação é resolvida **em runtime** no formulário público, não na estrutura do template. O `SurveyQuestion` da Seção 1 contém as perguntas genéricas sobre professores; o formulário replica essas perguntas para cada professor vinculado ao segmento/unidade do respondente.

---

## 5. Models

### 5.1 Unit

| Atributo | Tipo | Detalhes |
|----------|------|----------|
| **Tabela** | `units` | |
| **Traits** | HasFactory, HasUuid, SoftDeletes | |

| Campo | Tipo DB | Constraints | Notas |
|-------|---------|-------------|-------|
| `id` | `uuid()->primary()` | PK | |
| `name` | `string(100)` | required | Nome da unidade escolar |
| `slug` | `string(100)` | unique | URL-friendly identifier (padronizado com Subject; ver revisão DBA) |
| `is_active` | `boolean()->default(true)` | index | |
| `created_at` | `timestamp` | auto | |
| `updated_at` | `timestamp` | auto | |
| `deleted_at` | `timestamp` | nullable, index | SoftDeletes |

**Índices:** `unique(slug)`, `index(is_active)`

**Relacionamentos:**
- `belongsToMany` → User (via `unit_user`)
- `belongsToMany` → Teacher (via `unit_teacher`)
- `hasMany` → SegmentTeacher, Enrollment, SurveyBatch, SurveyResponse

**Scopes:**
- `scopeActive(Builder $query)` — `where('is_active', true)`

**Casts:**
```
'is_active' => 'boolean'
```

---

### 5.2 User

| Atributo | Tipo | Detalhes |
|----------|------|----------|
| **Tabela** | `users` | |
| **Traits** | HasFactory, HasUuid, SoftDeletes, Notifiable | |
| **Extends** | Authenticatable | Filament panel user |
| **Tenant-scoped** | Não (global; unidades via pivot `unit_user`) | |

| Campo | Tipo DB | Constraints | Notas |
|-------|---------|-------------|-------|
| `id` | `uuid()->primary()` | PK | |
| `name` | `string(100)` | required | |
| `email` | `string(254)` | index | RFC 5321; unique por `whereNull('deleted_at')` — validação no código (MySQL sem índice parcial) |
| `password` | `string` | required | Hashed |
| `role` | `string(20)->default('operator')` | index | Cast → UserRole enum |
| `is_active` | `boolean()->default(true)` | index | |
| `email_verified_at` | `timestamp` | nullable | |
| `remember_token` | `rememberToken()` | | |
| `created_at` | `timestamp` | auto | |
| `updated_at` | `timestamp` | auto | |
| `deleted_at` | `timestamp` | nullable, index | SoftDeletes |

**Índices:** `index(email)`, `index(role)`, `index(is_active)`

**Unique constraint:** `Rule::unique('users', 'email')->whereNull('deleted_at')` — no código, pois MySQL não suporta índice parcial.

**Relacionamentos:**
- `belongsToMany` → Unit (via `unit_user`)

**Pivot `unit_user`**

| Campo | Tipo DB | Constraints |
|-------|---------|-------------|
| `id` | `uuid()->primary()` | PK |
| `unit_id` | `foreignUuid()->constrained('units')->cascadeOnDelete()` | |
| `user_id` | `foreignUuid()->constrained('users')->cascadeOnDelete()` | |
| `created_at` | `timestamp` | auto |
| `updated_at` | `timestamp` | auto |

**Índices:** `unique(unit_id, user_id)`, `index('user_id')` — o segundo cobre lookups reversos (`getTenants()`, `$user->units`).

**Casts:**
```
'role' => UserRole::class,
'is_active' => 'boolean',
'email_verified_at' => 'datetime',
'password' => 'hashed',
```

**Scopes:**
- `scopeActive(Builder $query)` — `where('is_active', true)`
- `scopeByRole(Builder $query, UserRole $role)` — `where('role', $role)`
- `scopeAdmins(Builder $query)` — `where('role', UserRole::Admin)`

**Filament Tenancy:** `User` implementa `HasTenants`; `getTenants(): Collection` retorna `$this->units` (eager-friendly).

---

### 5.3 Segment

| Atributo | Tipo | Detalhes |
|----------|------|----------|
| **Tabela** | `segments` | |
| **Traits** | HasFactory, HasUuid, SoftDeletes | |
| **Tenant-scoped** | Não (global) | |

| Campo | Tipo DB | Constraints | Notas |
|-------|---------|-------------|-------|
| `id` | `uuid()->primary()` | PK | |
| `name` | `string(50)` | required | "Maternal 1", "1º ano", "3ª série" |
| `slug` | `string(100)` | unique | "maternal-1", "1o-ano", "3a-serie" |
| `group` | `string(20)` | index | Cast → SegmentGroup enum (EI, EF1, EF2, EM) |
| `sort_order` | `unsignedInteger()->default(0)` | index | Ordenação na UI |
| `is_active` | `boolean()->default(true)` | index | |
| `created_at` | `timestamp` | auto | |
| `updated_at` | `timestamp` | auto | |
| `deleted_at` | `timestamp` | nullable, index | SoftDeletes |

**Índices:** `unique(slug)`, `index(group)`, `index(sort_order)`, `index(is_active)`

**Relacionamentos:**
- `hasMany` → Enrollment
- `belongsToMany` → Teacher (via `segment_teachers`)
- `belongsToMany` → Subject (via `segment_subject` pivot — ver nota)

**Nota sobre Segment ↔ Subject:** O DRF menciona "disciplinas vinculadas a segmentos (N:N)". Criar pivot `segment_subject` para essa relação, utilizada na Seção 1 (EF2/EM) para determinar quais disciplinas o respondente avalia.

**Pivot adicional:** `segment_subject`

| Campo | Tipo DB | Constraints |
|-------|---------|-------------|
| `id` | `uuid()->primary()` | PK |
| `segment_id` | `foreignUuid()->constrained()->cascadeOnDelete()` | |
| `subject_id` | `foreignUuid()->constrained()->cascadeOnDelete()` | |
| `created_at` | `timestamp` | auto |
| `updated_at` | `timestamp` | auto |

**Índice:** `unique(segment_id, subject_id)`

**Casts:**
```
'group' => SegmentGroup::class,
'is_active' => 'boolean',
```

**Scopes:**
- `scopeByGroup(Builder $query, SegmentGroup $group)` — `where('group', $group)`
- `scopeActive(Builder $query)` — `where('is_active', true)`
- `scopeOrdered(Builder $query)` — `orderBy('sort_order')`

---

### 5.4 Subject

| Atributo | Tipo | Detalhes |
|----------|------|----------|
| **Tabela** | `subjects` | |
| **Traits** | HasFactory, HasUuid, SoftDeletes | |
| **Tenant-scoped** | Não (global) | |

| Campo | Tipo DB | Constraints | Notas |
|-------|---------|-------------|-------|
| `id` | `uuid()->primary()` | PK | |
| `name` | `string(100)` | required | "Matemática", "Português" |
| `slug` | `string(100)` | unique | |
| `is_active` | `boolean()->default(true)` | index | |
| `sort_order` | `unsignedInteger()->default(0)` | index | |
| `created_at` | `timestamp` | auto | |
| `updated_at` | `timestamp` | auto | |
| `deleted_at` | `timestamp` | nullable, index | SoftDeletes |

**Índices:** `unique(slug)`, `index(is_active)`, `index(sort_order)`

**Relacionamentos:**
- `belongsToMany` → Segment (via `segment_subject`)
- `hasMany` → SegmentTeacher (quando EF2/EM, professor vinculado a disciplina)

**Casts:**
```
'is_active' => 'boolean',
```

**Scopes:**
- `scopeActive(Builder $query)` — `where('is_active', true)`
- `scopeOrdered(Builder $query)` — `orderBy('sort_order')`

---

### 5.5 Teacher

| Atributo | Tipo | Detalhes |
|----------|------|----------|
| **Tabela** | `teachers` | |
| **Traits** | HasFactory, HasUuid, SoftDeletes | |
| **Tenant-scoped** | Não (global; unidades via pivot `unit_teacher`) | |

| Campo | Tipo DB | Constraints | Notas |
|-------|---------|-------------|-------|
| `id` | `uuid()->primary()` | PK | |
| `name` | `string(100)` | required | |
| `email` | `string(254)` | nullable, index | RFC 5321 |
| `is_active` | `boolean()->default(true)` | index | |
| `created_at` | `timestamp` | auto | |
| `updated_at` | `timestamp` | auto | |
| `deleted_at` | `timestamp` | nullable, index | SoftDeletes |

**Índices:** `index(is_active)`, `index(email)`

**Relacionamentos:**
- `belongsToMany` → Unit (via `unit_teacher`)
- `belongsToMany` → Segment (via `segment_teachers`)
- `hasMany` → SegmentTeacher

**Pivot `unit_teacher`**

| Campo | Tipo DB | Constraints |
|-------|---------|-------------|
| `id` | `uuid()->primary()` | PK |
| `unit_id` | `foreignUuid()->constrained('units')->cascadeOnDelete()` | |
| `teacher_id` | `foreignUuid()->constrained('teachers')->cascadeOnDelete()` | |
| `created_at` | `timestamp` | auto |
| `updated_at` | `timestamp` | auto |

**Índices:** `unique(unit_id, teacher_id)`, `index('teacher_id')` — o segundo cobre lookups reversos (`$teacher->units`).

**Casts:**
```
'is_active' => 'boolean',
```

**Scopes:**
- `scopeActive(Builder $query)` — `where('is_active', true)`

**Soft delete vs `segment_teachers`:** `cascadeOnDelete()` não roda em soft delete. Um `Teacher` apenas *soft-deleted* pode deixar linhas em `segment_teachers` apontando para registro “invisível” ao `belongsTo` padrão. **Mitigação obrigatória (escolher uma ou combinar):** (a) `TeacherObserver` que, no `deleted`, executa *hard delete* das linhas de `segment_teachers` daquele `teacher_id`; (b) queries do formulário público e do admin sempre com `whereHas('teacher')` / escopo que exclua professores deletados. Documentar a escolha no código.

---

### 5.6 SegmentTeacher

| Atributo | Tipo | Detalhes |
|----------|------|----------|
| **Tabela** | `segment_teachers` | |
| **Traits** | HasFactory, HasUuid | |
| **Tenant-scoped** | Sim (`unit_id` direto na tabela) | |

| Campo | Tipo DB | Constraints | Notas |
|-------|---------|-------------|-------|
| `id` | `uuid()->primary()` | PK | |
| `unit_id` | `foreignUuid()->constrained('units')->cascadeOnDelete()` | index | Unidade à qual o vínculo professor↔segmento pertence |
| `segment_id` | `foreignUuid()->constrained()->cascadeOnDelete()` | | |
| `teacher_id` | `foreignUuid()->constrained()->cascadeOnDelete()` | | |
| `subject_id` | `foreignUuid()->nullable()->constrained()->nullOnDelete()` | | Null para EI/EF1 (professor fixo); preenchido para EF2/EM |
| `created_at` | `timestamp` | auto | |
| `updated_at` | `timestamp` | auto | |

**Índices e unicidade (MySQL + `subject_id` NULL):** em MySQL, `UNIQUE` com coluna nullable permite **várias** linhas com `subject_id IS NULL` para o mesmo `(unit_id, segment_id, teacher_id)` — violando a intenção de negócio para EI/EF1. **Obrigatório antes de implementar:** (1) **Índice funcional** (MySQL 8.0.13+) criado via `DB::statement()`, p.ex. único sobre expressão do tipo `COALESCE(subject_id, '00000000-0000-0000-0000-000000000000')` em conjunto com `unit_id`, `segment_id`, `teacher_id` (usar UUID sentinela reservado **não** persistido como FK real), **ou** coluna gerada persistida que materialize esse `COALESCE` e uma `unique()` sobre ela; (2) **validação na aplicação** ao criar/atualizar `SegmentTeacher` espelhando a mesma regra. Manter também `index('teacher_id')` para filtros por professor (além do que a FK pode criar implicitamente).

**Invariante `unit_id` ↔ `unit_teacher`:** não existe FK que garanta que o professor pertença à unidade do vínculo. **Obrigatório:** serviço de criação/alteração de `SegmentTeacher` deve validar que existe linha em `unit_teacher` com o mesmo `unit_id` e `teacher_id`. Opcional: trigger MySQL para a mesma checagem em ambiente que exija defesa em profundidade.

**Auditoria (P2 / RF012):** avaliar `created_by` (`foreignUuid` nullable → `users`) nesta tabela e em `Enrollment` se a trilha de “quem cadastrou” for necessária além de `SurveyBatch`.

**Nota:** Sem SoftDeletes — pivot enriquecida gerenciada via sync/detach. Ao vincular professor a uma unidade em `unit_teacher`, os registros em `segment_teachers` devem usar o mesmo `unit_id`.

**Relacionamentos:**
- `belongsTo` → Unit, Segment, Teacher, Subject (nullable)

**Scopes:**
- `scopeForUnit(Builder $query, string $unitId)` — `where('unit_id', $unitId)`
- `scopeForSegment(Builder $query, string $segmentId)` — `where('segment_id', $segmentId)`
- `scopeForSubject(Builder $query, string $subjectId)` — `where('subject_id', $subjectId)`

---

### 5.7 Student

| Atributo | Tipo | Detalhes |
|----------|------|----------|
| **Tabela** | `students` | |
| **Traits** | HasFactory, HasUuid, SoftDeletes | |
| **Tenant-scoped** | Não (global) | |

| Campo | Tipo DB | Constraints | Notas |
|-------|---------|-------------|-------|
| `id` | `uuid()->primary()` | PK | |
| `name` | `string(100)` | required | Nome do aluno |
| `guardian_name` | `string(100)` | nullable | Nome do responsável |
| `guardian_email` | `string(254)` | nullable | Email do responsável (RFC 5321) |
| `guardian_phone` | `string(20)` | nullable | Telefone do responsável |
| `is_active` | `boolean()->default(true)` | index | |
| `created_at` | `timestamp` | auto | |
| `updated_at` | `timestamp` | auto | |
| `deleted_at` | `timestamp` | nullable, index | SoftDeletes |

**Índices:** `index(name)`, `index(is_active)`

**Relacionamentos:**
- `hasMany` → Enrollment

**Casts:**
```
'is_active' => 'boolean',
```

**Scopes:**
- `scopeActive(Builder $query)` — `where('is_active', true)`

---

### 5.8 Enrollment

| Atributo | Tipo | Detalhes |
|----------|------|----------|
| **Tabela** | `enrollments` | |
| **Traits** | HasFactory, HasUuid, SoftDeletes | |
| **Tenant-scoped** | Sim (`unit_id`) | |

| Campo | Tipo DB | Constraints | Notas |
|-------|---------|-------------|-------|
| `id` | `uuid()->primary()` | PK | |
| `student_id` | `foreignUuid()->constrained()->cascadeOnDelete()` | | |
| `unit_id` | `foreignUuid()->constrained('units')->cascadeOnDelete()` | | Tenant FK |
| `segment_id` | `foreignUuid()->constrained()->cascadeOnDelete()` | | Série do aluno neste ano |
| `registration_code` | `string(30)` | index | Matrícula do aluno (código digitado no formulário público) |
| `year` | `unsignedSmallInteger()` | index | Ano letivo (2026, 2027...) |
| `is_active` | `boolean()->default(true)` | index | |
| `created_at` | `timestamp` | auto | |
| `updated_at` | `timestamp` | auto | |
| `deleted_at` | `timestamp` | nullable, index | SoftDeletes |

**Índices:**
- `unique(student_id, unit_id, year)` — um aluno só pode estar matriculado uma vez por unidade/ano
- `unique(registration_code, unit_id, year)` — matrícula única por unidade/ano (para lookup público)
- `index(unit_id, year)` — queries de listagem por tenant/ano
- `index(segment_id)`

**Relacionamentos:**
- `belongsTo` → Student, Unit, Segment
- `hasMany` → SurveyResponse

**Casts:**
```
'is_active' => 'boolean',
'year' => 'integer',
```

**Scopes:**
- `scopeCurrentYear(Builder $query)` — `where('year', now()->year)`
- `scopeActive(Builder $query)` — `where('is_active', true)`
- `scopeByRegistrationCode(Builder $query, string $code)` — `where('registration_code', $code)`

**Auditoria (P2 / RF012):** opcional `created_by` (`foreignUuid` nullable → `users`) se além de `SurveyBatch` for necessário registrar o autor da matrícula.

---

### 5.9 Survey

| Atributo | Tipo | Detalhes |
|----------|------|----------|
| **Tabela** | `surveys` | |
| **Traits** | HasFactory, HasUuid, SoftDeletes | |
| **Tenant-scoped** | Não (global) | |

| Campo | Tipo DB | Constraints | Notas |
|-------|---------|-------------|-------|
| `id` | `uuid()->primary()` | PK | |
| `title` | `string(200)` | required | "Pesquisa NPS 2026" |
| `description` | `text` | nullable | Descrição/instruções |
| `is_active` | `boolean()->default(true)` | index | |
| `created_at` | `timestamp` | auto | |
| `updated_at` | `timestamp` | auto | |
| `deleted_at` | `timestamp` | nullable, index | SoftDeletes |

**Índices:** `index(is_active)`

**Relacionamentos:**
- `hasMany` → SurveySection
- `hasMany` → SurveyBatch

**Casts:**
```
'is_active' => 'boolean',
```

---

### 5.10 SurveySection

| Atributo | Tipo | Detalhes |
|----------|------|----------|
| **Tabela** | `survey_sections` | |
| **Traits** | HasFactory, HasUuid, SoftDeletes | |
| **Tenant-scoped** | Não (global) | |

| Campo | Tipo DB | Constraints | Notas |
|-------|---------|-------------|-------|
| `id` | `uuid()->primary()` | PK | |
| `survey_id` | `foreignUuid()->constrained()->cascadeOnDelete()` | | |
| `title` | `string(100)` | required | "Professores", "Coordenação", etc. |
| `description` | `text` | nullable | Instruções da seção |
| `type` | `string(30)` | index | Cast → SectionType enum |
| `sort_order` | `unsignedInteger()->default(0)` | index | Ordem da seção (1–9) |
| `is_active` | `boolean()->default(true)` | index | |
| `created_at` | `timestamp` | auto | |
| `updated_at` | `timestamp` | auto | |
| `deleted_at` | `timestamp` | nullable, index | SoftDeletes |

**Índices:** `index(survey_id, sort_order)`, `index(type)`

**Relacionamentos:**
- `belongsTo` → Survey
- `hasMany` → SurveyQuestion

**Casts:**
```
'type' => SectionType::class,
'is_active' => 'boolean',
```

**Scopes:**
- `scopeOrdered(Builder $query)` — `orderBy('sort_order')`
- `scopeActive(Builder $query)` — `where('is_active', true)`

---

### 5.11 SurveyQuestion

| Atributo | Tipo | Detalhes |
|----------|------|----------|
| **Tabela** | `survey_questions` | |
| **Traits** | HasFactory, HasUuid, SoftDeletes | |
| **Tenant-scoped** | Não (global) | |

| Campo | Tipo DB | Constraints | Notas |
|-------|---------|-------------|-------|
| `id` | `uuid()->primary()` | PK | |
| `survey_section_id` | `foreignUuid()->constrained()->cascadeOnDelete()` | | |
| `code` | `string(30)` | unique | Código estável para referência no JSON: "S1Q1", "S2Q3", "S9NPS" |
| `text` | `text` | required | Texto da pergunta |
| `type` | `string(30)` | index | Cast → QuestionType enum |
| `is_required` | `boolean()->default(true)` | | |
| `sort_order` | `unsignedInteger()->default(0)` | index | |
| `is_active` | `boolean()->default(true)` | index | |
| `created_at` | `timestamp` | auto | |
| `updated_at` | `timestamp` | auto | |
| `deleted_at` | `timestamp` | nullable, index | SoftDeletes |

**Índices:** `unique(code)`, `index(survey_section_id, sort_order)`, `index(type)`

**Relacionamentos:**
- `belongsTo` → SurveySection

**Casts:**
```
'type' => QuestionType::class,
'is_required' => 'boolean',
'is_active' => 'boolean',
```

**Scopes:**
- `scopeOrdered(Builder $query)` — `orderBy('sort_order')`
- `scopeActive(Builder $query)` — `where('is_active', true)`

---

### 5.12 SurveyBatch

| Atributo | Tipo | Detalhes |
|----------|------|----------|
| **Tabela** | `survey_batches` | |
| **Traits** | HasFactory, HasUuid, SoftDeletes | |
| **Tenant-scoped** | Sim (`unit_id`) | |

| Campo | Tipo DB | Constraints | Notas |
|-------|---------|-------------|-------|
| `id` | `uuid()->primary()` | PK | |
| `unit_id` | `foreignUuid()->constrained('units')->cascadeOnDelete()` | index | Tenant FK |
| `survey_id` | `foreignUuid()->constrained()->cascadeOnDelete()` | | |
| `title` | `string(200)` | required | "Pesquisa 1º Semestre 2026" |
| `description` | `text` | nullable | |
| `status` | `string(20)->default('draft')` | index | Cast → SurveyBatchStatus enum |
| `public_token` | `string(64)` | unique, nullable | Token opaco para URL pública; gerado na ativação |
| `starts_at` | `timestamp` | nullable | Início do período de respostas |
| `ends_at` | `timestamp` | nullable, index | Fim do período; usado pelo scheduler |
| `activated_at` | `timestamp` | nullable | Quando foi ativado |
| `closed_at` | `timestamp` | nullable | Quando foi encerrado |
| `created_by` | `foreignUuid()->nullable()->constrained('users')->nullOnDelete()` | | Auditoria |
| `created_at` | `timestamp` | auto | |
| `updated_at` | `timestamp` | auto | |
| `deleted_at` | `timestamp` | nullable, index | SoftDeletes |

**Índices:** `unique(public_token)`, `index(unit_id, status)`, `index(status, ends_at)` — scheduler; opcional `index(['status', 'starts_at', 'ends_at'])` se filtros por período ganharem volume; `index(survey_id)`

**Relacionamentos:**
- `belongsTo` → Unit, Survey, User (createdBy)
- `hasMany` → SurveyResponse

**Casts:**
```
'status' => SurveyBatchStatus::class,
'starts_at' => 'datetime',
'ends_at' => 'datetime',
'activated_at' => 'datetime',
'closed_at' => 'datetime',
```

**Scopes:**
- `scopeActive(Builder $query)` — `where('status', SurveyBatchStatus::Active)`
- `scopeExpired(Builder $query)` — `where('status', SurveyBatchStatus::Active)->where('ends_at', '<', now())`
- `scopeAcceptingResponses(Builder $query)` — `where('status', SurveyBatchStatus::Active)->where('starts_at', '<=', now())->where('ends_at', '>=', now())`

---

### 5.13 SurveyResponse

| Atributo | Tipo | Detalhes |
|----------|------|----------|
| **Tabela** | `survey_responses` | |
| **Traits** | HasFactory, HasUuid, SoftDeletes | |
| **Tenant-scoped** | Sim (`unit_id`) | |

| Campo | Tipo DB | Constraints | Notas |
|-------|---------|-------------|-------|
| `id` | `uuid()->primary()` | PK | |
| `survey_batch_id` | `foreignUuid()->constrained()->cascadeOnDelete()` | | |
| `enrollment_id` | `foreignUuid()->constrained()->cascadeOnDelete()` | | |
| `unit_id` | `foreignUuid()->constrained('units')->cascadeOnDelete()` | index | Desnormalizado para queries de dashboard |
| `segment_id` | `foreignUuid()->constrained()->cascadeOnDelete()` | index | Desnormalizado: snapshot do segmento |
| `respondent_type` | `string(20)` | index | Cast → RespondentType enum; desnormalizado |
| `respondent_name` | `string(100)` | | Desnormalizado: snapshot nome aluno/responsável |
| `answers` | `json` | required | Schema definido na seção 6 |
| `is_completed` | `boolean()->default(false)` | index | |
| `completed_at` | `timestamp` | nullable | |
| `ip_address` | `string(45)` | nullable | IPv4/IPv6 para auditoria |
| `user_agent` | `string(500)` | nullable | Browser info para auditoria |
| `created_at` | `timestamp` | auto | |
| `updated_at` | `timestamp` | auto | |
| `deleted_at` | `timestamp` | nullable, index | SoftDeletes |

**Índices:**
- `unique(enrollment_id, survey_batch_id)` — **RN05**: uma resposta por matrícula/lote (vale para linhas não apagadas fisicamente; ver abaixo)
- `index(survey_batch_id, is_completed)` — listagem de respostas por lote
- `index(unit_id, segment_id, is_completed)` — dashboard: NPS por segmento
- `index(is_completed, completed_at)` — queries de respostas completas

**SoftDeletes vs resubmissão:** com `SoftDeletes`, o `unique(enrollment_id, survey_batch_id)` do MySQL ainda considera linhas com `deleted_at` preenchido. Para permitir nova resposta no mesmo par matrícula/lote após “exclusão” lógica, a operação de negócio deve usar `forceDelete()` na resposta anterior **ou** a constraint/fluxo deve ser redesenhado (p.ex. validação apenas em `whereNull('deleted_at')` sem unique abrangendo soft-deleted — não é o modelo atual). **Decisão v1:** documentar no `CompleteSurveyResponseAction` se resubmissão após soft-delete é permitida; se sim, usar `forceDelete` na resposta antiga antes de inserir a nova.

**Relacionamentos:**
- `belongsTo` → SurveyBatch, Enrollment, Unit, Segment

**Casts:**
```
'answers' => 'array',
'respondent_type' => RespondentType::class,
'is_completed' => 'boolean',
'completed_at' => 'datetime',
```

**Scopes:**
- `scopeCompleted(Builder $query)` — `where('is_completed', true)`
- `scopeByBatch(Builder $query, string $batchId)` — `where('survey_batch_id', $batchId)`
- `scopeBySegment(Builder $query, string $segmentId)` — `where('segment_id', $segmentId)`

### 5.14 Diagrama de Relacionamentos

```
Unit ──belongsToMany──→ User (via unit_user)
Unit ──belongsToMany──→ Teacher (via unit_teacher)
Unit ──hasMany──→ SegmentTeacher
Unit ──hasMany──→ Enrollment
Unit ──hasMany──→ SurveyBatch
Unit ──hasMany──→ SurveyResponse

User ──belongsToMany──→ Unit (via unit_user)

Teacher ──belongsToMany──→ Unit (via unit_teacher)

Student ──hasMany──→ Enrollment

Enrollment ──belongsTo──→ Student
Enrollment ──belongsTo──→ Unit
Enrollment ──belongsTo──→ Segment
Enrollment ──hasMany──→ SurveyResponse

Segment ──hasMany──→ Enrollment
Segment ──belongsToMany──→ Teacher (via segment_teachers)
Segment ──belongsToMany──→ Subject (via segment_subject)

Teacher ──hasMany──→ SegmentTeacher
Teacher ──belongsToMany──→ Segment (via segment_teachers)

SegmentTeacher ──belongsTo──→ Unit
SegmentTeacher ──belongsTo──→ Segment
SegmentTeacher ──belongsTo──→ Teacher
SegmentTeacher ──belongsTo──→ Subject (nullable)

Subject ──belongsToMany──→ Segment (via segment_subject)
Subject ──hasMany──→ SegmentTeacher

Survey ──hasMany──→ SurveySection
Survey ──hasMany──→ SurveyBatch

SurveySection ──belongsTo──→ Survey
SurveySection ──hasMany──→ SurveyQuestion

SurveyQuestion ──belongsTo──→ SurveySection

SurveyBatch ──belongsTo──→ Unit
SurveyBatch ──belongsTo──→ Survey
SurveyBatch ──belongsTo──→ User (createdBy)
SurveyBatch ──hasMany──→ SurveyResponse

SurveyResponse ──belongsTo──→ SurveyBatch
SurveyResponse ──belongsTo──→ Enrollment
SurveyResponse ──belongsTo──→ Unit
SurveyResponse ──belongsTo──→ Segment
```

---

## 6. JSON Schema para Respostas

### 6.1 Estrutura

O campo `answers` do `SurveyResponse` armazena um JSON com a seguinte estrutura:

**Charset:** banco e conexão Laravel devem usar `utf8mb4` com collation `utf8mb4_unicode_ci` (ou equivalente do projeto) para armazenar texto e JSON com suporte completo a Unicode.

```json
{
  "version": "1.0",
  "sections": {
    "S1": {
      "teachers": {
        "<teacher_uuid>": {
          "subject_id": "<subject_uuid_or_null>",
          "teacher_name": "Prof. João",
          "questions": {
            "S1Q1": { "value": 4 },
            "S1Q2": { "value": "nsa" },
            "S1Q3": { "value": 5 },
            "S1Q4": { "value": 3 },
            "S1Q5": { "value": 2 },
            "S1Q6": { "value": 4 }
          }
        },
        "<another_teacher_uuid>": {
          "subject_id": "<subject_uuid>",
          "teacher_name": "Prof. Maria",
          "questions": {
            "S1Q1": { "value": 5 },
            "S1Q2": { "value": 5 },
            "S1Q3": { "value": 4 },
            "S1Q4": { "value": 5 },
            "S1Q5": { "value": 5 },
            "S1Q6": { "value": 5 }
          }
        }
      }
    },
    "S2": {
      "questions": {
        "S2Q1": { "value": 4 },
        "S2Q2": { "value": "nsa" },
        "S2Q3": { "value": 5 },
        "S2Q4": { "value": 3 },
        "S2Q5": { "value": 4 },
        "S2Q6": { "value": 5 }
      }
    },
    "S3": {
      "questions": { "S3Q1": { "value": 4 }, "...": "..." }
    },
    "S4": {
      "questions": { "S4Q1": { "value": 5 }, "...": "..." }
    },
    "S5": {
      "questions": { "S5Q1": { "value": 3 }, "...": "..." }
    },
    "S6": {
      "questions": { "S6Q1": { "value": 4 }, "...": "..." }
    },
    "S7": {
      "questions": { "S7Q1": { "value": 5 }, "...": "..." }
    },
    "S8": {
      "questions": { "S8Q1": { "value": 4 }, "...": "..." }
    },
    "S9": {
      "questions": {
        "S9NPS": { "value": 9 },
        "S9T1": { "value": "Gosto muito da escola." },
        "S9T2": { "value": "Melhorar a cantina." },
        "S9T3": { "value": "" }
      }
    }
  }
}
```

### 6.2 Convenções

| Aspecto | Convenção |
|---------|-----------|
| **Chave de seção** | `S{n}` onde `n` = sort_order da SurveySection (S1–S9) |
| **Chave de pergunta** | `code` da SurveyQuestion (ex: "S2Q1", "S9NPS") — estável via seeder |
| **Valor numérico (1–5)** | `integer` |
| **Valor NPS (0–10)** | `integer` |
| **NSA** | `"nsa"` (string) — permite distinguir de null (não respondido) |
| **Texto livre** | `string` |
| **Seção 1 (Professores)** | Chave extra `teachers` agrupando por `teacher_uuid`; inclui `subject_id` para rastreio da disciplina e `teacher_name` como snapshot |
| **Versão** | `"version": "1.0"` — permite evoluir o schema sem quebrar respostas históricas |

### 6.3 Considerações para Queries de Dashboard

Para calcular NPS, o `NpsCalculationService` precisa extrair valores do JSON. Estratégias:

1. **MySQL JSON functions** (`JSON_EXTRACT`, `JSON_UNQUOTE`) — funciona para queries simples.
2. **Pós-processamento em PHP** — carregar respostas e calcular em memória. Mais flexível para a Seção 1 (estrutura aninhada com professores).
3. **Cache de resultados** — cachear agregações por batch/segmento/teacher para evitar reprocessamento.

**Recomendação v1:** processar em PHP com cache. O volume esperado (centenas de respostas por lote, não milhares) permite essa abordagem sem problemas de performance. Se o volume crescer, considerar materializar os valores em tabela auxiliar `survey_answer_values`.

---

## 7. Enums

Todos os enums seguem o padrão `.ai/docs/enums.md`: `string` backed, `HasLabel`, `HasColor`, `HasIcon`, labels com `__()`.

**Nota Filament v5:** Ícones nos Resources usam `Heroicon` enum (`Heroicon::OutlinedXxx`), não string `'heroicon-o-xxx'`. Nos enums, `getIcon()` ainda retorna string (padrão dos contratos Filament `HasIcon`).

### 7.1 UserRole

```
App\Enums\UserRole

case Admin = 'admin'       → Label: Administrador | Color: primary  | Icon: heroicon-o-shield-check
case Operator = 'operator' → Label: Operador      | Color: info     | Icon: heroicon-o-user

Transições: N/A (sem state machine)
```

**Permissões mínimas:**

| Ação | Admin | Operador |
|------|:-----:|:--------:|
| Gerenciar usuários | ✅ | ❌ |
| CRUD Segmentos (read-only, via seeder) | ✅ | ✅ (read) |
| CRUD Disciplinas | ✅ | ✅ |
| CRUD Professores / SegmentTeacher | ✅ | ✅ |
| CRUD Alunos / Matrículas | ✅ | ✅ |
| Importar CSV | ✅ | ✅ |
| CRUD Template Pesquisa | ✅ | ❌ |
| Criar/Ativar Lote | ✅ | ✅ |
| Fechar Lote | ✅ | ✅ |
| Reabrir Lote (Closed → Active) | ✅ | ❌ |
| Visualizar Respostas | ✅ | ✅ |
| Dashboard NPS | ✅ | ✅ |

### 7.2 SegmentGroup

```
App\Enums\SegmentGroup

case EI = 'ei'   → Label: Educação Infantil       | Color: info    | Icon: heroicon-o-face-smile
case EF1 = 'ef1' → Label: Ensino Fundamental I     | Color: success | Icon: heroicon-o-academic-cap
case EF2 = 'ef2' → Label: Ensino Fundamental II    | Color: warning | Icon: heroicon-o-book-open
case EM = 'em'   → Label: Ensino Médio             | Color: primary | Icon: heroicon-o-building-library

Métodos auxiliares:
  - isTeacherBySubject(): bool — true para EF2 e EM
  - isRespondentGuardian(): bool — true para EI e EF1
  - respondentType(): RespondentType — retorna o tipo conforme RN01
```

### 7.3 QuestionType

```
App\Enums\QuestionType

case Scale1to5 = 'scale_1_to_5'   → Label: Escala 1-5     | Color: info    | Icon: heroicon-o-star
case Scale0to10 = 'scale_0_to_10' → Label: Escala 0-10     | Color: primary | Icon: heroicon-o-chart-bar
case FreeText = 'free_text'        → Label: Texto Livre     | Color: gray    | Icon: heroicon-o-pencil-square
```

### 7.4 SectionType

```
App\Enums\SectionType

case Teachers = 'teachers'                     → Label: Professores           | Color: info
case Coordination = 'coordination'             → Label: Coordenação           | Color: success
case Secretariat = 'secretariat'               → Label: Secretaria            | Color: warning
case PhysicalStructure = 'physical_structure'  → Label: Estrutura Física      | Color: primary
case Cafeteria = 'cafeteria'                   → Label: Cantina               | Color: amber
case SocialMedia = 'social_media'              → Label: Redes Sociais         | Color: violet
case Chaplaincy = 'chaplaincy'                 → Label: Capelania             | Color: rose
case Institutional = 'institutional'           → Label: Avaliação Institucional| Color: gray
case NpsFinal = 'nps_final'                    → Label: NPS Final             | Color: danger
```

### 7.5 SurveyBatchStatus

```
App\Enums\SurveyBatchStatus

case Draft = 'draft'     → Label: Rascunho  | Color: gray    | Icon: heroicon-o-pencil
case Active = 'active'   → Label: Ativo     | Color: success | Icon: heroicon-o-play
case Closed = 'closed'   → Label: Encerrado | Color: danger  | Icon: heroicon-o-lock-closed

Transições:
  Draft → Active (Admin, Operador)
  Active → Closed (Admin, Operador, Scheduler)
  Closed → Active (somente Admin — reabertura)

canTransitionTo():
  Draft  → [Active]
  Active → [Closed]
  Closed → [Active]  // somente Admin — verificação via Policy, não no enum
```

### 7.6 RespondentType

```
App\Enums\RespondentType

case Student = 'student'     → Label: Aluno        | Color: info    | Icon: heroicon-o-user
case Guardian = 'guardian'   → Label: Responsável   | Color: warning | Icon: heroicon-o-users
```

---

## 8. Exceções de Domínio

Conforme `.ai/docs/error-handling.md`, hierarquia:

```
App\Exceptions\
├── BusinessException.php             (base)
└── Survey\
    └── SurveyException.php           (domínio NPS)
```

### 8.1 SurveyException

```
App\Exceptions\Survey\SurveyException extends BusinessException

Cenários (static factory methods):

1. invalidRegistrationCode(string $code)
   - message: "Registration code '{$code}' not found"
   - userMessage: __('survey.errors.invalid_registration_code')
   - Quando: matrícula digitada não existe no banco

2. noEnrollmentCurrentYear(string $code, int $year)
   - message: "No active enrollment found for code '{$code}' in year {$year}"
   - userMessage: __('survey.errors.no_enrollment_current_year')
   - Quando: matrícula existe mas não tem enrollment no ano corrente

3. batchNotAcceptingResponses(string $batchId)
   - message: "Batch {$batchId} is not accepting responses (status or period)"
   - userMessage: __('survey.errors.batch_not_accepting_responses')
   - Quando: lote não está Active ou está fora do período starts_at/ends_at

4. duplicateResponse(string $enrollmentId, string $batchId)
   - message: "Duplicate response: enrollment {$enrollmentId} already responded to batch {$batchId}"
   - userMessage: __('survey.errors.duplicate_response')
   - Quando: RN05 violado — já existe resposta completa para essa matrícula/lote

5. unauthorizedBatchReopen(string $batchId)
   - message: "User not authorized to reopen batch {$batchId}"
   - userMessage: __('survey.errors.unauthorized_batch_reopen')
   - Quando: Operador tenta reabrir lote (somente Admin pode)

6. invalidBatchTransition(string $from, string $to)
   - message: "Invalid batch status transition: {$from} -> {$to}"
   - userMessage: __('survey.errors.invalid_batch_transition')
   - Quando: transição de status inválida no SurveyBatchStatus
```

---

## 9. Events e Listeners

Conforme `.ai/docs/events.md`. Agrupados no domínio `Survey/`.

### 9.1 Events

```
App\Events\Survey\

1. SurveyBatchActivated
   - Propriedades: readonly SurveyBatch $batch
   - Trigger: ActivateBatchAction (status Draft→Active ou Closed→Active)

2. SurveyBatchClosed
   - Propriedades: readonly SurveyBatch $batch, readonly bool $isAutomatic
   - Trigger: CloseBatchAction ou CloseExpiredSurveyBatchesJob

3. SurveyResponseCompleted
   - Propriedades: readonly SurveyResponse $response
   - Trigger: CompleteSurveyResponseAction
```

### 9.2 Listeners

| Event | Listener | Sync/Queue | Ação |
|-------|----------|:----------:|------|
| SurveyBatchActivated | LogBatchActivation | Sync | Log info com batch_id, unit_id, activated_by |
| SurveyBatchActivated | GenerateBatchPublicToken | Sync | Gera `public_token` se ainda não existe |
| SurveyBatchClosed | LogBatchClosure | Sync | Log info com batch_id, is_automatic, total_responses |
| SurveyBatchClosed | InvalidateNpsCache | Sync | `Cache::forget("nps:batch:{$batch->id}:*")` |
| SurveyResponseCompleted | LogResponseCompletion | Sync | Log info com response_id, batch_id, enrollment_id |
| SurveyResponseCompleted | InvalidateNpsCache | Sync | Invalida cache NPS do batch afetado |

**Justificativa para Sync:** nenhum listener realiza I/O externo (email, API) na v1. Todos são operações rápidas (log, cache). Se futuramente adicionarmos notificações por email, migrar para `ShouldQueue` com `$afterCommit = true`.

### 9.3 Subscriber (Opcional)

Considerar `SurveyBatchSubscriber` se os listeners crescerem além de 3 events. Na v1, auto-discovery por type-hint é suficiente.

---

## 10. Camadas

### 10.1 DTOs

```
App\DTOs\

- ImportStudentRow          — dados de uma linha do CSV (name, registration_code, guardian_name, segment_slug, etc.)
- SurveyResponseData        — dados do formulário público antes de persistir
- NpsResult                 — resultado calculado: score, promoters, detractors, neutrals, total, nsa_count
- NpsFilterData             — filtros do dashboard: batch_id, segment_id, subject_id, teacher_id
```

### 10.2 Services

```
App\Services\

- EnrollmentResolverService
    resolve(string $registrationCode, string $unitId): Enrollment
    Lógica: busca enrollment pelo registration_code + unit_id + ano corrente + is_active
    Lança: SurveyException::invalidRegistrationCode() ou noEnrollmentCurrentYear()

- NpsCalculationService
    calculateByQuestion(NpsFilterData $filters): Collection<NpsResult>
    calculateGeneral(NpsFilterData $filters): NpsResult
    Lógica: extrai valores do JSON, aplica RN02/RN03/RN08, retorna NpsResult
    Cache: Cache::remember() com TTL de 15 min, invalidado por listener

- SurveyBatchLinkService
    generatePublicUrl(SurveyBatch $batch): string
    resolveByToken(string $token): SurveyBatch
    Lógica: gera token opaco (Str::random(64)), monta URL pública
```

### 10.3 Actions

```
App\Actions\Survey\

- ActivateBatchAction
    execute(SurveyBatch $batch, User $user): SurveyBatch
    Valida transição (Draft→Active ou Closed→Active com permissão Admin)
    Gera public_token via SurveyBatchLinkService
    Seta starts_at/ends_at se fornecidos, activated_at = now()
    Dispatcha SurveyBatchActivated

- CloseBatchAction
    execute(SurveyBatch $batch, ?User $user = null, bool $isAutomatic = false): SurveyBatch
    Valida transição (Active→Closed)
    Seta closed_at = now()
    Dispatcha SurveyBatchClosed

- CompleteSurveyResponseAction
    execute(SurveyResponseData $data, Enrollment $enrollment, SurveyBatch $batch): SurveyResponse
    Verifica RN04 (batch accepting responses)
    Verifica RN05 (duplicate response): se existir resposta anterior soft-deleted e a política permitir nova submissão, `forceDelete` antes do insert (ver §5.13)
    Persiste SurveyResponse com answers JSON, is_completed=true, completed_at=now()
    Desnormaliza respondent_type, respondent_name, segment_id, unit_id
    Dispatcha SurveyResponseCompleted

- ImportStudentsCsvAction
    execute(UploadedFile $file, string $unitId): ImportResult
    Valida CSV (headers, tipos, tamanho)
    Para cada linha: cria/atualiza Student + Enrollment
    Retorna contadores (imported, updated, skipped, errors)
```

### 10.4 Jobs

```
App\Jobs\Survey\

- CloseExpiredSurveyBatchesJob
    ShouldQueue, ShouldBeUnique
    $tries = 3, $backoff = [10, 30, 60]
    $queue = 'default'
    handle(): busca batches com status=Active e ends_at < now(), executa CloseBatchAction para cada
    Idempotente: verifica status antes de fechar (se já foi fechado, pula)
```

---

## 11. Scheduling

Conforme `.ai/docs/scheduling.md`, configurado em `routes/console.php`.

```
// routes/console.php

Schedule::command('survey:close-expired-batches')
    ->everyFiveMinutes()
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/close-expired-batches.log'));

Schedule::command('model:prune')
    ->daily()
    ->timezone('America/Sao_Paulo')
    ->onOneServer();
```

### 11.1 Command: CloseExpiredSurveyBatches

```
Signature: survey:close-expired-batches
Description: Encerra lotes de pesquisa com período expirado
Frequência: everyFiveMinutes()
Idempotência: verifica status=Active antes de fechar; se já Closed, pula
Lógica: busca SurveyBatch::expired()->get(), para cada um dispatcha CloseExpiredSurveyBatchesJob
```

**Justificativa para `everyFiveMinutes`:** granularidade suficiente para fechar lotes pouco depois de `ends_at`. Não precisa ser ao minuto.

---

## 12. Performance

### 12.1 Índices para Dashboard

As queries do dashboard NPS filtram por combinações de batch, segmento, disciplina e professor. Índices críticos na `survey_responses`:

| Índice | Query Pattern |
|--------|---------------|
| `index(survey_batch_id, is_completed)` | Respostas por lote |
| `index(unit_id, segment_id, is_completed)` | NPS por segmento/unidade |
| `unique(enrollment_id, survey_batch_id)` | RN05 + lookup rápido |

Para filtros por professor e disciplina, os valores estão dentro do JSON (`answers.sections.S1.teachers.<uuid>`). Essas queries serão processadas em PHP, não em SQL.

### 12.2 Índices Adicionais

| Tabela | Índice | Uso |
|--------|--------|-----|
| `survey_batches` | `index(status, ends_at)` | Scheduler: buscar lotes expirados |
| `survey_batches` | `index(unit_id, status)` | Listagem de lotes por tenant |
| `survey_batches` | `index(status, starts_at, ends_at)` (opcional) | Filtros por período em escala |
| `enrollments` | `unique(registration_code, unit_id, year)` | Lookup público por matrícula |
| `enrollments` | `index(unit_id, year)` | Listagem por tenant/ano |
| `segment_teachers` | unicidade funcional / gerada + `COALESCE(subject_id, …)` | Ver §5.6 — não confiar só em `UNIQUE` com `NULL` |
| `segment_teachers` | `index('teacher_id')` | Listagens por professor |
| `unit_user` | `unique(unit_id, user_id)` | Um vínculo por par usuário/unidade |
| `unit_user` | `index('user_id')` | Lookups reversos (`getTenants`) |
| `unit_teacher` | `unique(unit_id, teacher_id)` | Um vínculo por par professor/unidade |
| `unit_teacher` | `index('teacher_id')` | Lookups reversos (`$teacher->units`) |

### 12.3 Cache

| Chave | TTL | Invalidação | Dados |
|-------|-----|-------------|-------|
| `nps:batch:{batchId}:general` | 15 min | SurveyResponseCompleted, SurveyBatchClosed | NpsResult geral do lote |
| `nps:batch:{batchId}:segment:{segmentId}` | 15 min | Idem | NpsResult por segmento |
| `nps:batch:{batchId}:teacher:{teacherId}` | 15 min | Idem | NpsResult por professor |
| `nps:batch:{batchId}:subject:{subjectId}` | 15 min | Idem | NpsResult por disciplina |

**Estratégia:** `Cache::remember()` com tags `['nps', "batch:{$batchId}"]`. Invalidação via `Cache::tags(["batch:{$batchId}"])->flush()` no listener `InvalidateNpsCache`.

**Nota:** requer Redis como cache driver (já configurado no PROJECT.md: `queue_driver: "redis"`).

### 12.4 Eager Loading

| Contexto | Relationships |
|----------|---------------|
| Filament SurveyBatchResource table | `['unit', 'survey', 'createdBy']` |
| Filament SurveyResponseResource table | `['surveyBatch', 'enrollment.student', 'enrollment.segment']` |
| Formulário público (após identificação) | `['segment', 'unit', 'student']` no Enrollment |
| Dashboard NPS | `['enrollment.segment']` nos SurveyResponses (se processamento PHP) |

---

## 13. Filament Resources

### 13.1 Painel e Tenancy

**Painel:** `app` (id=`app`, path=`/app`)
**Tenant:** `Unit` — configurar via `->tenant(Unit::class)` no `AppPanelProvider`
**Discover path:** `App\Filament\Resources` (já configurado)

### 13.2 Grupos de Navegação

| Grupo | Sort | Resources |
|-------|:----:|-----------|
| Principal | 1 | Dashboard (NPS) |
| Cadastros | 2 | Segments, Subjects, Teachers, Students, Enrollments |
| Pesquisas | 3 | Surveys, SurveyBatches |
| Relatórios | 4 | (dashboard page — sem resource) |
| Configurações | 99 | Units, Users |

### 13.3 Resources — Formato Blueprint

---

#### UnitResource

```
Resource: UnitResource
  Command: php artisan make:filament-resource Unit --generate --soft-deletes --view --panel=app --no-interaction
  Location: App\Filament\Resources\Units\UnitResource
  Structure:
    - UnitResource.php (final, LIMPO)
    - Schemas/UnitForm.php (final)
    - Schemas/UnitInfolist.php (final)
    - Tables/UnitsTable.php (final)
    - Pages/ (CreateUnit, EditUnit, ListUnits, ViewUnit)
  SoftDeletes: getRecordRouteBindingEloquentQuery()
  Icon: Heroicon::OutlinedBuildingOffice2
  Navigation:
    Group: Configurações
    Sort: 1
  Form:
    Field: name
      Component: Filament\Forms\Components\TextInput
      Validation: required, max:100
    Field: slug
      Component: Filament\Forms\Components\TextInput
      Validation: required, max:50, unique (ignoreRecord)
    Field: is_active
      Component: Filament\Forms\Components\Toggle
      Config: default(true)
  Infolist:
    Entry: name → TextEntry
    Entry: slug → TextEntry
    Entry: is_active → IconEntry::boolean()
    Entry: created_at → TextEntry::dateTime()
  Table:
    Column: name → TextColumn, searchable, sortable
    Column: slug → TextColumn
    Column: is_active → IconColumn::boolean()
    Column: created_at → TextColumn::dateTime(), sortable, toggleable(isToggledHiddenByDefault: true)
    Filter: TrashedFilter
  RecordActions: [View, Edit, Delete]
  ToolbarActions: [BulkActionGroup → [DeleteBulk]]
```

---

#### UserResource

```
Resource: UserResource
  Command: php artisan make:filament-resource User --generate --soft-deletes --view --panel=app --no-interaction
  Location: App\Filament\Resources\Users\UserResource
  Structure:
    - UserResource.php (final, LIMPO)
    - Schemas/UserForm.php (final)
    - Schemas/UserInfolist.php (final)
    - Tables/UsersTable.php (final)
    - Pages/ (CreateUser, EditUser, ListUsers, ViewUser)
  SoftDeletes: getRecordRouteBindingEloquentQuery()
  Icon: Heroicon::OutlinedUsers
  Navigation:
    Group: Configurações
    Sort: 2
  Note: User é global; listagem no tenant corrente via `whereHas('units', …)` ou attach automático.
  getEloquentQuery(): restringir a usuários ligados ao `Filament::getTenant()` (salvo super-admin, se existir).
  Form:
    Field: name → TextInput, required, max:100
    Field: email → TextInput, email, required, unique(ignoreRecord, whereNull deleted_at)
    Field: password → TextInput, password, required (create), nullable (edit), confirmed
    Field: role → Select, options(UserRole::class), required, default(UserRole::Operator)
    Field: is_active → Toggle, default(true)
    Field: units → CheckboxList ou Select multiple, relationship('units', 'name'), searchable, preload
      — mínimo 1 unidade; na criação, pré-selecionar o tenant corrente; Admin pode adicionar outras unidades
  Infolist:
    Entry: name → TextEntry
    Entry: email → TextEntry
    Entry: role → TextEntry::badge()
    Entry: units → TextEntry (lista de nomes via `units` relationship)
    Entry: is_active → IconEntry::boolean()
    Entry: created_at → TextEntry::dateTime()
  Table:
    Column: name → TextColumn, searchable, sortable
    Column: email → TextColumn, searchable
    Column: units.name → TextColumn (lista/badge), label: Unidades
    Column: role → TextColumn::badge(), sortable
    Column: is_active → IconColumn::boolean()
    Column: created_at → TextColumn::dateTime(), sortable, toggleable(hidden)
    Filter: role → SelectFilter, options(UserRole::class)
    Filter: TrashedFilter
  RecordActions: [View, Edit, Delete]
  ToolbarActions: [BulkActionGroup → [DeleteBulk]]
  Policy: UserPolicy — Admin: full CRUD; Operador: sem acesso
```

---

#### SegmentResource

```
Resource: SegmentResource
  Command: php artisan make:filament-resource Segment --generate --soft-deletes --view --panel=app --no-interaction
  Location: App\Filament\Resources\Segments\SegmentResource
  Structure:
    - SegmentResource.php (final, LIMPO)
    - Schemas/SegmentForm.php (final)
    - Schemas/SegmentInfolist.php (final)
    - Tables/SegmentsTable.php (final)
    - Pages/ (ListSegments, ViewSegment) — sem Create/Edit (dados via seeder)
  SoftDeletes: getRecordRouteBindingEloquentQuery()
  Icon: Heroicon::OutlinedAcademicCap
  Navigation:
    Group: Cadastros
    Sort: 1
  Note: Read-only para ambos os perfis. Dados gerenciados via seeder.
  Table:
    Column: name → TextColumn, searchable, sortable
    Column: group → TextColumn::badge(), sortable
    Column: sort_order → TextColumn, sortable
    Column: is_active → IconColumn::boolean()
    Filter: group → SelectFilter, options(SegmentGroup::class)
  RecordActions: [View]
  ToolbarActions: []
```

---

#### SubjectResource

```
Resource: SubjectResource
  Command: php artisan make:filament-resource Subject --generate --soft-deletes --view --panel=app --no-interaction
  Location: App\Filament\Resources\Subjects\SubjectResource
  Structure:
    - SubjectResource.php (final, LIMPO)
    - Schemas/SubjectForm.php (final)
    - Schemas/SubjectInfolist.php (final)
    - Tables/SubjectsTable.php (final)
    - Pages/ (CreateSubject, EditSubject, ListSubjects, ViewSubject)
  SoftDeletes: getRecordRouteBindingEloquentQuery()
  Icon: Heroicon::OutlinedBookOpen
  Navigation:
    Group: Cadastros
    Sort: 2
  Form:
    Field: name → TextInput, required, max:100
    Field: is_active → Toggle, default(true)
    Field: segments → Select, multiple, relationship('segments', 'name'), searchable, preload
  Table:
    Column: name → TextColumn, searchable, sortable
    Column: segments_count → TextColumn (withCount)
    Column: is_active → IconColumn::boolean()
    Filter: TrashedFilter
  RecordActions: [View, Edit, Delete]
  ToolbarActions: [BulkActionGroup → [DeleteBulk]]
```

---

#### TeacherResource

```
Resource: TeacherResource
  Command: php artisan make:filament-resource Teacher --generate --soft-deletes --view --panel=app --no-interaction
  Location: App\Filament\Resources\Teachers\TeacherResource
  Structure:
    - TeacherResource.php (final, LIMPO)
    - Schemas/TeacherForm.php (final)
    - Schemas/TeacherInfolist.php (final)
    - Tables/TeachersTable.php (final)
    - Pages/ (CreateTeacher, EditTeacher, ListTeachers, ViewTeacher)
    - RelationManagers/SegmentTeachersRelationManager.php
  SoftDeletes: getRecordRouteBindingEloquentQuery()
  Icon: Heroicon::OutlinedUserGroup
  Navigation:
    Group: Cadastros
    Sort: 3
  Note: Teacher é global; `getEloquentQuery()` filtra `whereHas('units', fn ($q) => $q->whereKey(Filament::getTenant()))`.
  Na criação: após salvar, `attach` do teacher ao tenant corrente em `unit_teacher` (e opcionalmente CheckboxList `units` para Admin incluir outras unidades).
  Form:
    Field: name → TextInput, required, max:100
    Field: email → TextInput, email, nullable
    Field: is_active → Toggle, default(true)
    Field: units (opcional, visível p/ Admin) → CheckboxList, relationship('units', 'name'), min 1
  Table:
    Column: name → TextColumn, searchable, sortable
    Column: email → TextColumn, searchable
    Column: units.name → TextColumn (lista/badge), label: Unidades (visível só se listagem global; no tenant, redundante)
    Column: segment_teachers_count → TextColumn (withCount), label: Vínculos
    Column: is_active → IconColumn::boolean()
    Filter: TrashedFilter
  RelationManagers:
    - SegmentTeachersRelationManager (hasMany → segmentTeachers)
      Form: segment_id → Select relationship, subject_id → Select relationship (nullable)
      unit_id → preenchido automaticamente com `Filament::getTenant()->getKey()` (hidden ou mutator no create)
      Table: segment.name, subject.name (nullable), created_at
  Infolist:
    Entry: units → TextEntry (lista de unidades via relationship)
  RecordActions: [View, Edit, Delete]
  ToolbarActions: [BulkActionGroup → [DeleteBulk]]
```

---

#### StudentResource

```
Resource: StudentResource
  Command: php artisan make:filament-resource Student --generate --soft-deletes --view --panel=app --no-interaction
  Location: App\Filament\Resources\Students\StudentResource
  Structure:
    - StudentResource.php (final, LIMPO)
    - Schemas/StudentForm.php (final)
    - Schemas/StudentInfolist.php (final)
    - Tables/StudentsTable.php (final)
    - Pages/ (CreateStudent, EditStudent, ListStudents, ViewStudent)
    - RelationManagers/EnrollmentsRelationManager.php
    - Actions/ImportStudentsCsvAction.php
  SoftDeletes: getRecordRouteBindingEloquentQuery()
  Icon: Heroicon::OutlinedUser
  Navigation:
    Group: Cadastros
    Sort: 4
  Note: Student é global, mas listagem filtrada por enrollments do tenant corrente
  Form:
    Field: name → TextInput, required, max:100
    Field: guardian_name → TextInput, nullable, max:100
    Field: guardian_email → TextInput, email, nullable
    Field: guardian_phone → TextInput, nullable, tel, max:20
    Field: is_active → Toggle, default(true)
  Table:
    Column: name → TextColumn, searchable, sortable
    Column: guardian_name → TextColumn, searchable
    Column: enrollments_count → TextColumn (withCount), label: Matrículas
    Column: is_active → IconColumn::boolean()
    Filter: TrashedFilter
  HeaderActions: [Create, ImportStudentsCsvAction (custom — abre modal com FileUpload)]
  RelationManagers:
    - EnrollmentsRelationManager (hasMany → enrollments)
  RecordActions: [View, Edit, Delete]
  ToolbarActions: [BulkActionGroup → [DeleteBulk]]
```

---

#### EnrollmentResource

```
Resource: EnrollmentResource
  Command: php artisan make:filament-resource Enrollment --generate --soft-deletes --view --panel=app --no-interaction
  Location: App\Filament\Resources\Enrollments\EnrollmentResource
  Structure:
    - EnrollmentResource.php (final, LIMPO)
    - Schemas/EnrollmentForm.php (final)
    - Schemas/EnrollmentInfolist.php (final)
    - Tables/EnrollmentsTable.php (final)
    - Pages/ (CreateEnrollment, EditEnrollment, ListEnrollments, ViewEnrollment)
  SoftDeletes: getRecordRouteBindingEloquentQuery()
  Icon: Heroicon::OutlinedClipboardDocumentList
  Navigation:
    Group: Cadastros
    Sort: 5
  Note: Tenant-scoped (unit_id automático). Alternativa: gerenciar via RelationManager em StudentResource
  Form:
    Field: student_id → Select, relationship('student', 'name'), searchable, required
    Field: segment_id → Select, relationship('segment', 'name'), searchable, required
    Field: registration_code → TextInput, required, max:30
    Field: year → TextInput, numeric, required, default(now()->year)
    Field: is_active → Toggle, default(true)
  Table:
    Column: student.name → TextColumn, searchable, sortable
    Column: segment.name → TextColumn, sortable
    Column: registration_code → TextColumn, searchable
    Column: year → TextColumn, sortable
    Column: is_active → IconColumn::boolean()
    Filter: segment → SelectFilter, relationship('segment', 'name')
    Filter: year → SelectFilter
    Filter: TrashedFilter
  RecordActions: [View, Edit, Delete]
  ToolbarActions: [BulkActionGroup → [DeleteBulk]]
```

---

#### SurveyResource

```
Resource: SurveyResource
  Command: php artisan make:filament-resource Survey --generate --soft-deletes --view --panel=app --no-interaction
  Location: App\Filament\Resources\Surveys\SurveyResource
  Structure:
    - SurveyResource.php (final, LIMPO)
    - Schemas/SurveyForm.php (final)
    - Schemas/SurveyInfolist.php (final)
    - Tables/SurveysTable.php (final)
    - Pages/ (CreateSurvey, EditSurvey, ListSurveys, ViewSurvey)
    - RelationManagers/SurveySectionsRelationManager.php
  SoftDeletes: getRecordRouteBindingEloquentQuery()
  Icon: Heroicon::OutlinedDocumentText
  Navigation:
    Group: Pesquisas
    Sort: 1
  Note: Global (não tenant-scoped). Apenas Admin pode editar.
  Form:
    Field: title → TextInput, required, max:200
    Field: description → Textarea, nullable, rows:3
    Field: is_active → Toggle, default(true)
  Table:
    Column: title → TextColumn, searchable, sortable
    Column: survey_sections_count → TextColumn (withCount)
    Column: is_active → IconColumn::boolean()
    Column: created_at → TextColumn::dateTime(), sortable
    Filter: TrashedFilter
  RelationManagers:
    - SurveySectionsRelationManager (hasMany → surveySections)
      Form: title, description, type (SectionType), sort_order, is_active
      Table: title, type (badge), sort_order, questions_count (withCount)
      SubRelation: SurveyQuestionsRelationManager (acessível via ViewSurveySection ou inline repeater)
  RecordActions: [View, Edit, Delete]
  ToolbarActions: [BulkActionGroup → [DeleteBulk]]
  Policy: SurveyPolicy — Admin: full CRUD; Operador: read-only
```

**Nota:** Para gerenciar Questions dentro de Sections, duas opções:
1. **RelationManager aninhado** — `SurveyQuestionsRelationManager` no ViewSurveySection (mais complexo).
2. **Repeater no form da Section** — `Repeater::make('surveyQuestions')` no SurveySectionsRelationManager.

Recomendação: usar Repeater inline para simplificar a v1.

---

#### SurveyBatchResource

```
Resource: SurveyBatchResource
  Command: php artisan make:filament-resource SurveyBatch --generate --soft-deletes --view --panel=app --no-interaction
  Location: App\Filament\Resources\SurveyBatches\SurveyBatchResource
  Structure:
    - SurveyBatchResource.php (final, LIMPO)
    - Schemas/SurveyBatchForm.php (final)
    - Schemas/SurveyBatchInfolist.php (final)
    - Tables/SurveyBatchesTable.php (final)
    - Pages/ (CreateSurveyBatch, EditSurveyBatch, ListSurveyBatches, ViewSurveyBatch)
    - Actions/ActivateBatchAction.php (Filament Action wrapper)
    - Actions/CloseBatchAction.php (Filament Action wrapper)
    - Actions/ReopenBatchAction.php (Filament Action wrapper, visible only for Admin)
    - Actions/CopyLinkAction.php (copia link público para clipboard)
    - RelationManagers/SurveyResponsesRelationManager.php
    - Widgets/SurveyBatchStatsWidget.php
  SoftDeletes: getRecordRouteBindingEloquentQuery()
  Icon: Heroicon::OutlinedRectangleStack
  Navigation:
    Group: Pesquisas
    Sort: 2
  Note: Tenant-scoped (unit_id automático)
  Form:
    Field: survey_id → Select, relationship('survey', 'title'), required, searchable
    Field: title → TextInput, required, max:200
    Field: description → Textarea, nullable
    Field: starts_at → DateTimePicker, nullable
    Field: ends_at → DateTimePicker, nullable, after:starts_at
  Infolist:
    Entry: title, survey.title, status (badge), public_token, starts_at, ends_at, activated_at, closed_at, created_by.name, responses_count, created_at
  Table:
    Column: title → TextColumn, searchable, sortable
    Column: status → TextColumn::badge(), sortable
    Column: survey.title → TextColumn
    Column: starts_at → TextColumn::dateTime()
    Column: ends_at → TextColumn::dateTime()
    Column: survey_responses_count → TextColumn (withCount), label: Respostas
    Column: created_at → TextColumn::dateTime(), sortable
    Filter: status → SelectFilter, options(SurveyBatchStatus::class)
    Filter: TrashedFilter
  RecordActions: [View, Edit, ActivateBatch, CloseBatch, ReopenBatch, CopyLink, Delete]
  ToolbarActions: [BulkActionGroup → [DeleteBulk]]
  RelationManagers:
    - SurveyResponsesRelationManager (hasMany → surveyResponses)
      Table: enrollment.student.name, enrollment.registration_code, respondent_type (badge), is_completed (icon), completed_at
      Note: read-only, sem create/edit
  Widgets:
    - SurveyBatchStatsWidget (total respostas, completas, NPS geral)
```

---

#### SurveyResponseResource

```
Resource: SurveyResponseResource
  Command: php artisan make:filament-resource SurveyResponse --generate --soft-deletes --view --panel=app --no-interaction
  Location: App\Filament\Resources\SurveyResponses\SurveyResponseResource
  Structure:
    - SurveyResponseResource.php (final, LIMPO)
    - Schemas/SurveyResponseInfolist.php (final) — sem Form (respostas vêm do formulário público)
    - Tables/SurveyResponsesTable.php (final)
    - Pages/ (ListSurveyResponses, ViewSurveyResponse)
  SoftDeletes: getRecordRouteBindingEloquentQuery()
  Icon: Heroicon::OutlinedChatBubbleLeftRight
  Navigation:
    Group: Pesquisas
    Sort: 3
  Note: Tenant-scoped. Read-only — sem Create/Edit. Visualizar respostas JSON formatadas no Infolist.
  Infolist:
    Entry: surveyBatch.title, enrollment.student.name, enrollment.registration_code
    Entry: respondent_type (badge), respondent_name
    Entry: is_completed (icon), completed_at
    Entry: answers → KeyValueEntry ou custom component para exibir JSON formatado
    Entry: ip_address, user_agent (toggleable, hidden by default)
  Table:
    Column: surveyBatch.title → TextColumn, sortable
    Column: enrollment.student.name → TextColumn, searchable
    Column: enrollment.registration_code → TextColumn, searchable
    Column: respondent_type → TextColumn::badge()
    Column: is_completed → IconColumn::boolean()
    Column: completed_at → TextColumn::dateTime(), sortable
    Filter: surveyBatch → SelectFilter, relationship('surveyBatch', 'title')
    Filter: respondent_type → SelectFilter, options(RespondentType::class)
    Filter: is_completed → TernaryFilter
    Filter: TrashedFilter
  RecordActions: [View]
  ToolbarActions: []
  Policy: SurveyResponsePolicy — ambos perfis: read-only
```

---

### 13.4 Dashboard NPS (Custom Page + Widgets)

```
App\Filament\Pages\NpsDashboard

Tipo: Custom Filament Page (não Resource)
Route: /app/nps-dashboard
Navigation:
  Group: Relatórios
  Sort: 1
  Icon: Heroicon::OutlinedChartBar

Widgets:
  1. NpsOverviewStatsWidget (StatsOverview)
     - NPS Geral (0-10)
     - Total de Respostas
     - Taxa de Conclusão (%)
     - NPS por Pergunta (média 1-5)

  2. NpsBySegmentChartWidget (ChartWidget — bar chart)
     - NPS por segmento (eixo X = segmentos, eixo Y = score NPS)

  3. NpsBySectionChartWidget (ChartWidget — bar chart)
     - NPS médio por seção (Coordenação, Secretaria, etc.)

  4. NpsByTeacherTableWidget (TableWidget)
     - Tabela com professor, disciplina, NPS, total respostas
     - Filtro por segmento

Filtros (no header da page):
  - batch_id → Select (lotes do tenant)
  - segment_id → Select (segmentos)
  - subject_id → Select (disciplinas, dependente de segment EF2/EM)
  - teacher_id → Select (professores do tenant)

Nota: Widgets usam NpsCalculationService com cache. Polling interval: 60s.
```

---

## 14. Formulário Público (Livewire)

### 14.1 Rotas

```
// routes/web.php

Route::prefix('survey')->name('survey.')->group(function () {
    Route::get('/{token}', [PublicSurveyController::class, 'show'])
        ->name('show')
        ->middleware(['throttle:60,1']);

    Route::post('/{token}/identify', [PublicSurveyController::class, 'identify'])
        ->name('identify')
        ->middleware(['throttle:10,1']);

    Route::post('/{token}/submit', [PublicSurveyController::class, 'submit'])
        ->name('submit')
        ->middleware(['throttle:5,1']);
});
```

### 14.2 Estratégia de Acesso

**Recomendação: `public_token` opaco no `SurveyBatch`.**

| Opção | Prós | Contras |
|-------|------|---------|
| Signed URL (`URL::signedRoute`) | Segurança criptográfica, expiração nativa | URL longa, complexa de compartilhar, atrelada ao domínio |
| **Public token opaco** ✅ | URL curta e compartilhável, fácil copiar/colar, independente de domínio | Precisa gerar token único e validar no controller |

**URL resultante:** `https://nps.colegiopense.com.br/survey/aB3xY9kLmN2pQ7...` (64 chars, `Str::random(64)`)

### 14.3 Componentes Livewire

```
App\Livewire\Survey\

1. SurveyIdentification (class-based)
   - Exibe campo de matrícula (registration_code)
   - Valida via EnrollmentResolverService
   - Determina respondent_type (RN01)
   - Erro: exibe mensagem de SurveyException
   - Sucesso: redireciona para SurveyForm com enrollment data

2. SurveyForm (class-based)
   - Recebe enrollment (segment, unit) e batch
   - Carrega seções e perguntas do template (Survey→Sections→Questions)
   - Seção 1: carrega professores via SegmentTeacher (filtra por segment+unit; se EF2/EM, agrupa por Subject)
   - Renderiza perguntas com componentes Filament (InteractsWithForms)
   - Escala 1–5 + NSA: ToggleButtons ou RadioGroup customizado
   - Escala 0–10: ToggleButtons ou slider
   - Texto livre: Textarea
   - Navegação por seção (wizard-like ou scroll)
   - Submit: chama CompleteSurveyResponseAction
   - Sucesso: tela de agradecimento

3. SurveyThankYou (class-based ou SFC)
   - Tela estática de agradecimento pós-submissão
```

### 14.4 Layout e UX

- Layout público dedicado (sem Filament chrome): `resources/views/layouts/survey.blade.php`
- Responsivo (mobile-first) usando Tailwind
- Componentes Filament para inputs (InteractsWithForms)
- `#[Lazy]` para seções pesadas (Seção 1 com muitos professores)
- Sem autenticação — CSRF token obrigatório no POST
- Rate limiting nos endpoints (ver seção 15)

---

## 15. Segurança

### 15.1 Throttle em Endpoints Públicos

| Endpoint | Rate Limit | Justificativa |
|----------|:----------:|---------------|
| `GET /survey/{token}` | 60/min | Visualização; pode haver múltiplos acessos legítimos |
| `POST /survey/{token}/identify` | 10/min | Tentativas de matrícula; limitar brute force |
| `POST /survey/{token}/submit` | 5/min | Submissão; 1 resposta por matrícula é o esperado |

Configurar rate limiters customizados em `AppServiceProvider` ou `RouteServiceProvider` para keys por IP + token.

### 15.2 CSV Validation & Size

| Regra | Valor | Implementação |
|-------|-------|---------------|
| Tamanho máximo | 5 MB | `FileUpload::maxSize(5120)` + validation no ImportStudentsCsvAction |
| Tipo de arquivo | `.csv`, `.txt` | `FileUpload::acceptedFileTypes(['text/csv', 'text/plain'])` |
| Encoding | UTF-8 | Verificar BOM e encoding antes de processar |
| Colunas obrigatórias | name, registration_code, segment_slug | Validar headers na primeira linha |
| Limite de linhas | 5.000 por importação | Rejeitar arquivos com mais de 5k linhas na v1 |
| Sanitização | Strip HTML/scripts de todos os campos | `strip_tags()` + `htmlspecialchars()` |

### 15.3 Filament Policies

Cada Resource deve ter uma Policy registrada. Políticas mínimas:

| Resource | Admin | Operador |
|----------|:-----:|:--------:|
| UnitResource | CRUD | ❌ (hidden) |
| UserResource | CRUD | ❌ (hidden) |
| SegmentResource | Read | Read |
| SubjectResource | CRUD | CRUD |
| TeacherResource | CRUD | CRUD |
| StudentResource | CRUD | CRUD |
| EnrollmentResource | CRUD | CRUD |
| SurveyResource | CRUD | Read |
| SurveyBatchResource | CRUD + Reopen | CRU (sem Reopen) |
| SurveyResponseResource | Read | Read |

**Implementação:** `App\Policies\{Model}Policy` com métodos `viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`. Verificar `$user->role` contra `UserRole` enum.

### 15.4 Outras Medidas

- **CSRF:** ativo em todas as rotas POST públicas (middleware padrão Laravel).
- **XSS:** campos de texto livre no JSON são escapados na exibição (Blade auto-escape).
- **SQL Injection:** Eloquent ORM parameterizado; nenhuma query raw sem bindings.
- **Mass Assignment:** `$fillable` definido em todos os Models.
- **Sensitive Data:** `password` e `remember_token` no `$hidden` do User.

---

## 16. Factories e Seeders

### 16.1 Factories (todas obrigatórias)

| Factory | States |
|---------|--------|
| UnitFactory | `active()`, `inactive()` |
| UserFactory | `admin()`, `operator()`, `inactive()` |
| SegmentFactory | `ei()`, `ef1()`, `ef2()`, `em()` |
| SubjectFactory | `active()`, `inactive()` |
| TeacherFactory | `active()`, `inactive()` |
| SegmentTeacherFactory | `withSubject()`, `forUnit()` |
| StudentFactory | `withGuardian()`, `active()` |
| EnrollmentFactory | `currentYear()`, `active()` |
| SurveyFactory | `active()` |
| SurveySectionFactory | `teachers()`, `coordination()`, `npsFinal()` |
| SurveyQuestionFactory | `scale1to5()`, `scale0to10()`, `freeText()` |
| SurveyBatchFactory | `draft()`, `active()`, `closed()`, `expired()`, `withPublicToken()` |
| SurveyResponseFactory | `completed()`, `incomplete()`, `withAnswers()` |

### 16.2 Seeders (ordem de execução)

```
DatabaseSeeder
├── 1. UnitSeeder (2 unidades: firstOrCreate)
├── 2. UserSeeder (usuários + attach em `unit_user` — um mesmo usuário pode ter várias unidades)
├── 3. SegmentSeeder (16 segmentos: firstOrCreate)
├── 4. SubjectSeeder (disciplinas base: firstOrCreate)
├── 5. SegmentSubjectSeeder (pivot segment_subject para EF2/EM)
├── 6. SurveyTemplateSeeder (1 survey + 9 sections + perguntas: firstOrCreate)
└── 7. DevelopmentSeeder (apenas local/testing)
    ├── TeacherSeeder (professores globais + attach em `unit_teacher` por unidade)
    ├── SegmentTeacherSeeder (vínculos com `unit_id` explícito)
    ├── StudentSeeder (alunos fictícios via factory)
    ├── EnrollmentSeeder (matrículas)
    └── SurveyBatchSeeder (lotes de exemplo com respostas)
```

### 16.3 Segmentos (SegmentSeeder)

| # | Nome | Slug | Grupo | sort_order |
|---|------|------|-------|:----------:|
| 1 | Maternal 1 | maternal-1 | EI | 1 |
| 2 | Maternal 2 | maternal-2 | EI | 2 |
| 3 | Jardim 1 | jardim-1 | EI | 3 |
| 4 | Jardim 2 | jardim-2 | EI | 4 |
| 5 | 1º ano | 1o-ano | EF1 | 5 |
| 6 | 2º ano | 2o-ano | EF1 | 6 |
| 7 | 3º ano | 3o-ano | EF1 | 7 |
| 8 | 4º ano | 4o-ano | EF1 | 8 |
| 9 | 5º ano | 5o-ano | EF1 | 9 |
| 10 | 6º ano | 6o-ano | EF2 | 10 |
| 11 | 7º ano | 7o-ano | EF2 | 11 |
| 12 | 8º ano | 8o-ano | EF2 | 12 |
| 13 | 9º ano | 9o-ano | EF2 | 13 |
| 14 | 1ª série | 1a-serie | EM | 14 |
| 15 | 2ª série | 2a-serie | EM | 15 |
| 16 | 3ª série | 3a-serie | EM | 16 |

### 16.4 SurveyTemplateSeeder

Cria 1 Survey com 9 SurveySections e as perguntas correspondentes conforme Anexo C do DRF-001. Codes das perguntas seguem padrão `S{section}Q{n}` (ex: S1Q1, S2Q3, S9NPS, S9T1, S9T2, S9T3).

---

## 17. Fases de Implementação

Alinhadas ao Anexo D do DRF-001, com ordem de migrations detalhada.

### Fase 1 — Base (Infra + Tenancy)

**Entregável:** Painel Filament com login, tenancy por unidade, CRUD de usuários.

**Migrations (ordem):**
1. `create_units_table`
2. `create_users_table` (adicionar `role`, `is_active`; **sem** `unit_id`)
3. `create_unit_user_table` (pivot `unit_id` + `user_id`, `unique(unit_id, user_id)`, `index('user_id')`)

**Enums:** `UserRole`
**Seeders:** `UnitSeeder`, `UserSeeder`
**Resources:** `UnitResource`, `UserResource`
**Policies:** `UnitPolicy`, `UserPolicy`
**Config:** `AppPanelProvider` com tenancy por `Unit`

### Fase 2 — Cadastros

**Entregável:** CRUD de segmentos, disciplinas, professores, alunos e matrículas. Import CSV.

**Migrations (ordem):**
4. `create_segments_table`
5. `create_subjects_table`
6. `create_segment_subject_table` (pivot)
7. `create_teachers_table` (**sem** `unit_id`)
8. `create_unit_teacher_table` (pivot `unit_id` + `teacher_id`, `unique(unit_id, teacher_id)`, `index('teacher_id')`)
9. `create_segment_teachers_table` (coluna `unit_id` + FKs; **não** usar só `unique(..., subject_id)` com NULL — aplicar unicidade funcional/coluna gerada conforme §5.6; `index('teacher_id')`)
10. `create_students_table`
11. `create_enrollments_table`

**Enums:** `SegmentGroup`
**Seeders:** `SegmentSeeder`, `SubjectSeeder`, `SegmentSubjectSeeder`
**Resources:** `SegmentResource`, `SubjectResource`, `TeacherResource`, `StudentResource`, `EnrollmentResource`
**Actions:** `ImportStudentsCsvAction`
**Services:** `EnrollmentResolverService` (base)
**DTOs:** `ImportStudentRow`

### Fase 3 — Template de Pesquisa

**Entregável:** Admin configura seções e perguntas; seeder com template padrão.

**Migrations (ordem):**
12. `create_surveys_table`
13. `create_survey_sections_table`
14. `create_survey_questions_table`

**Enums:** `QuestionType`, `SectionType`
**Seeders:** `SurveyTemplateSeeder`
**Resources:** `SurveyResource` (com RelationManagers)
**Policies:** `SurveyPolicy`

### Fase 4 — Core (Lotes + Respostas + Formulário Público)

**Entregável:** Lotes com links, formulário público, respostas em JSON.

**Migrations (ordem):**
15. `create_survey_batches_table`
16. `create_survey_responses_table`

**Enums:** `SurveyBatchStatus`, `RespondentType`
**Services:** `SurveyBatchLinkService`, `EnrollmentResolverService` (completo)
**Actions:** `ActivateBatchAction`, `CloseBatchAction`, `CompleteSurveyResponseAction`
**Jobs:** `CloseExpiredSurveyBatchesJob`
**Events:** `SurveyBatchActivated`, `SurveyBatchClosed`, `SurveyResponseCompleted`
**Listeners:** todos da seção 9.2
**Exceptions:** `SurveyException`
**DTOs:** `SurveyResponseData`
**Resources:** `SurveyBatchResource`, `SurveyResponseResource`
**Livewire:** `SurveyIdentification`, `SurveyForm`, `SurveyThankYou`
**Scheduling:** `survey:close-expired-batches`
**Command:** `CloseExpiredSurveyBatches`

### Fase 5 — Dashboard NPS

**Entregável:** Dashboard NPS duplo com filtros.

**Services:** `NpsCalculationService`
**DTOs:** `NpsResult`, `NpsFilterData`
**Filament:** `NpsDashboard` page + widgets (Stats, Charts, Table)
**Cache:** configuração de cache para agregações NPS

---

## 18. Handoff

### 18.1 Próximos Passos

| Passo | Agente/Ação | Descrição |
|:-----:|-------------|-----------|
| 1 | **dba** | Revisão registrada em [DBA-REVIEW-DTA-001](DBA-REVIEW-DTA-001.md); itens P0–P2 incorporados na v1.2 deste DTA |
| 2 | **implementer** | Implementar Fase 1 (Base) — migrations, models, enums, seeders, panel config |
| 3 | **implementer** | Implementar Fase 2 (Cadastros) — models, resources, import CSV |
| 4 | **implementer** | Implementar Fase 3 (Template) — survey models, seeders, resource |
| 5 | **implementer** | Implementar Fase 4 (Core) — batches, respostas, formulário público Livewire |
| 6 | **implementer** | Implementar Fase 5 (Dashboard) — NpsCalculationService, widgets, page |
| 7 | **tester** | Escrever testes Pest por fase — models, services, actions, Livewire, Filament |
| 8 | **reviewer** | Code review após cada fase |
| 9 | **security** | Auditoria de segurança — throttle, validação CSV, policies, CSRF |

### 18.2 Comandos Sugeridos

- `/blueprint` para plano Filament detalhado por Resource
- `/feature` para implementação completa por fase
- Consultar `.ai/checklists.md` para checklist de cada artefato
- Consultar `.ai/docs/queues.md` para configuração do Job
- Consultar `.ai/docs/scheduling.md` para configuração do scheduler no Docker

### 18.3 Riscos e Trade-offs

| Risco | Mitigação |
|-------|-----------|
| JSON answers dificulta queries SQL complexas | Processar em PHP + cache; volume escolar viabiliza essa abordagem |
| Seção 1 (professores) com estrutura variável por segmento | Resolver em runtime no Livewire; JSON flexível suporta ambos os formatos |
| `public_token` sem expiração criptográfica | Token de 64 chars é suficientemente seguro; rate limit protege contra brute force |
| Desnormalizações em `survey_responses` | Documentadas na migration; mantidas consistentes pela CompleteSurveyResponseAction |
| User/Teacher N:N com Unit aumenta complexidade no Filament (query + sync pivot) | Mitigar com `getEloquentQuery()` + attach/sync explícito nos Resources e `unit_id` em `SegmentTeacher` |
| MySQL sem índice parcial para unique + soft deletes | Validação via `Rule::unique()->whereNull('deleted_at')` no código |
| `survey_responses` unique vs soft-delete impede resubmissão silenciosa | Definir política em Action: `forceDelete` da resposta anterior ou proibir nova submissão enquanto existir linha (mesmo soft-deleted) |
| Charset não utf8mb4 | Garantir `utf8mb4` / `utf8mb4_unicode_ci` no deploy (Docker, `config/database.php`) |

---

## Histórico de Revisões

| Versão | Data | Autor | Descrição |
|--------|------|-------|-----------|
| 1.2 | 2026-04-22 | — | Ajustes pós-revisão DBA ([DBA-REVIEW-DTA-001](DBA-REVIEW-DTA-001.md)): unicidade `segment_teachers` com `subject_id` NULL; invariante `unit_teacher`; índices em pivots e `teacher_id`; slugs/email RFC; SurveyResponse + soft delete; charset; índice opcional em `survey_batches`; `Teacher` soft delete vs pivot. |
| 1.1 | 2026-04-22 | — | `User` e `Teacher` N:N com `Unit` via `unit_user` e `unit_teacher`; `SegmentTeacher` com `unit_id` explícito. |
| 1.0 | 2026-04-22 | Architect Agent | Versão inicial baseada no DRF-001. |