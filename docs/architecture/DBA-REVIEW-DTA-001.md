# Revisão de Banco de Dados: Sistema NPS — Colégio Pense (DTA-001 v1.1)

**Documento:** Revisão DBA do modelo proposto em [DTA-001-nps.md](DTA-001-nps.md)  
**Data:** 2026-04-22  
**Referência:** DTA-001 v1.1  

---

## 1. Resumo executivo

O documento DTA-001 v1.1 apresenta uma estrutura bem normalizada (3NF), com nomenclatura de campos, tipos e enums em total conformidade com o padrão do projeto. A decisão de processar respostas JSON em PHP com cache é adequada ao volume esperado. Foram identificados **dois riscos críticos (P0)** que precisam ser resolvidos antes da implementação: o comportamento do `UNIQUE` com `NULL` no MySQL para `segment_teachers.subject_id`, e o tratamento de `Teacher` soft-deleted com registros em `segment_teachers` (sem SoftDeletes). Além disso, quatro pontos importantes (P1) e cinco sugestões (P2) completam o diagnóstico.

---

## 2. Conformidade com `.ai/docs/database.md`

| # | Critério | Status | Detalhe |
|---|----------|:------:|---------|
| 1 | PK UUID em todas as tabelas | OK | Todos os models usam `uuid()->primary()` |
| 2 | Booleanos com prefixo `is_` / `has_` | OK | `is_active`, `is_completed`, `is_required` |
| 3 | Ordenação com `sort_order` | OK | Segment, Subject, SurveySection, SurveyQuestion |
| 4 | Datas de evento com sufixo `_at` | OK | `starts_at`, `ends_at`, etc. |
| 5 | Status/tipo como `string` no banco | OK | Nenhum `enum()` SQL |
| 6 | Cast para Enum PHP | OK | Conforme DTA |
| 7 | Soft deletes em entidades de domínio | OK | Conforme DTA |
| 8 | Pivots simples sem SoftDeletes | OK | `unit_user`, `unit_teacher`, `segment_subject` |
| 9 | FK com ação explícita | OK | `cascadeOnDelete()` / `nullOnDelete()` |
| 10 | Índices | Atenção | Ver seção 4 — faltam índices explícitos em pivots |
| 11 | Desnormalizações documentadas | OK | Seção 4.1.3 do DTA |
| 12 | Auditoria `created_by` | Atenção | Apenas `SurveyBatch`; ver P2 |

---

## 3. Integridade referencial e unicidade

### 3.1 `segment_teachers` — UNIQUE com `subject_id` NULL (P0)

No MySQL, múltiplas linhas com `subject_id IS NULL` podem violar a intenção de unicidade sob `UNIQUE(unit_id, segment_id, teacher_id, subject_id)`.

**Recomendação:** índice funcional (MySQL 8.0.13+) com `COALESCE(subject_id, '00000000-0000-0000-0000-000000000000')` via `DB::statement()`, **e** validação na aplicação.

### 3.2 `survey_responses` — UNIQUE vs SoftDeletes (P1)

Soft-delete de resposta pode impedir nova submissão com a mesma constraint. Definir regra de negócio: se resubmissão for permitida, usar `forceDelete` ou ajustar constraint/validação com `whereNull('deleted_at')`.

### 3.3 Pivots e SoftDeletes (P1)

`cascadeOnDelete` não roda em soft delete. **Teacher** soft-deleted pode deixar `segment_teachers` apontando para professor “invisível” ao `belongsTo`. Recomendação: **Observer** em `Teacher` para remover (hard) linhas em `segment_teachers`, ou sempre `whereHas('teacher')` nas queries do formulário público.

### 3.4 Invariante `SegmentTeacher.unit_id` ↔ `unit_teacher` (P1)

Não há FK que garanta que o professor pertença à unidade do vínculo. Validar na camada de aplicação (e opcionalmente trigger MySQL).

---

## 4. Índices

- **P1:** `index('user_id')` em `unit_user` e `index('teacher_id')` em `unit_teacher` para lookups reversos (`getTenants()`, `$teacher->units`).
- **P2:** `index(['status', 'starts_at', 'ends_at'])` em `survey_batches` se filtros por período crescerem.
- **P2:** `index('teacher_id')` explícito em `segment_teachers` (além do que o FK pode criar implicitamente).
- **Enrollment:** `unique(registration_code, unit_id, year)` adequado para RN06 quando a query usa esses três campos.

---

## 5. Tipos e tamanhos

- `public_token` (64), `ip_address` (45), `registration_code` (30): adequados.
- `user_agent` (500): OK para quase todos os casos; alternativa `text` se quiser margem total.
- **P1:** Padronizar tamanho de `slug` (Segment 50 vs Subject 100) — sugerido `string(100)` onde fizer sentido.
- **P2:** Emails como `string(254)` explícito (RFC).

---

## 6. JSON `answers`

- Versionamento `1.0`: adequado; serviço de NPS deve ramificar por versão.
- Validação estrutural: manter na aplicação (CHECK JSON no MySQL é impraticável para manter).
- Charset: garantir `utf8mb4` / `utf8mb4_unicode_ci` no banco.

---

## 7. Ordem de migrations

A sequência descrita no DTA (Fases 1–4, migrations 1–16) está **consistente** com as dependências de FK; sem ciclos.

---

## 8. Priorização

### P0 — antes de implementar

| ID | Ação |
|----|------|
| P0-1 | Corrigir unicidade `segment_teachers` com `subject_id` NULL (índice funcional + app) |
| P0-2 | Tratar Teacher soft-deleted vs `segment_teachers` (Observer ou queries defensivas) |

### P1 — antes de produção

| ID | Ação |
|----|------|
| P1-1 | Documentar/decidir resubmissão após soft-delete de `SurveyResponse` |
| P1-2 | Validar `SegmentTeacher` contra `unit_teacher` |
| P1-3 | Índices `user_id` / `teacher_id` nos pivots |
| P1-4 | Padronizar `slug` |

### P2 — melhorias

| ID | Ação |
|----|------|
| P2-1 | Avaliar `created_by` em `Enrollment` / `SegmentTeacher` (RF012) |
| P2-2 | Índice composto em `survey_batches` para período |
| P2-3 | Índice `teacher_id` em `segment_teachers` |
| P2-4 | `string(254)` em emails |
| P2-5 | Confirmar charset no deploy |

---

## Handoff implementação

1. Migration `segment_teachers`: estratégia P0-1 (índice funcional ou coluna gerada).  
2. `TeacherObserver` ou equivalente para P0-2.  
3. Pivots: índices P1-3.  
4. Atualizar DTA ou DRF com decisão P1-1.  
5. Service de criação de `SegmentTeacher` com validação P1-2.

---

*Relatório produzido pelo agente **dba** com base no DTA-001 v1.1 e nas guidelines em `.ai/docs/`.*
