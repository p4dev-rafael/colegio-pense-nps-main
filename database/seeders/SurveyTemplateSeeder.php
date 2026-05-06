<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\QuestionType;
use App\Enums\SectionType;
use App\Models\Survey;
use App\Models\SurveySection;
use App\Models\SurveyQuestion;
use Illuminate\Database\Seeder;

/**
 * Single global survey template with 9 sections (DRF-001 Annex C).
 * Uses idempotent updateOrCreate keyed by stable codes/slugs.
 */
final class SurveyTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $survey = Survey::query()->updateOrCreate(
            ['title' => 'Pesquisa NPS — Colégio Pense'],
            [
                'description' => 'Template único de pesquisa de satisfação institucional.',
                'is_active' => true,
            ],
        );

        foreach ($this->template() as $sectionData) {
            $section = SurveySection::query()->updateOrCreate(
                [
                    'survey_id' => $survey->id,
                    'type' => $sectionData['type']->value,
                ],
                [
                    'title' => $sectionData['title'],
                    'description' => $sectionData['description'] ?? null,
                    'sort_order' => $sectionData['sort_order'],
                    'is_active' => true,
                ],
            );

            foreach ($sectionData['questions'] as $questionData) {
                SurveyQuestion::query()->updateOrCreate(
                    ['code' => $questionData['code']],
                    [
                        'survey_section_id' => $section->id,
                        'text' => $questionData['text'],
                        'type' => $questionData['type']->value,
                        'is_required' => $questionData['is_required'] ?? true,
                        'sort_order' => $questionData['sort_order'],
                        'is_active' => true,
                    ],
                );
            }
        }
    }

    /**
     * @return list<array{type: SectionType, title: string, description?: string, sort_order: int, questions: list<array{code: string, text: string, type: QuestionType, sort_order: int, is_required?: bool}>}>
     */
    private function template(): array
    {
        return [
            [
                'type' => SectionType::Teachers,
                'title' => 'Professores',
                'description' => 'Avalie cada professor nos critérios abaixo.',
                'sort_order' => 1,
                'questions' => $this->scaleQuestions('S1', [
                    'Domínio do conteúdo da disciplina',
                    'Clareza nas explicações',
                    'Relacionamento com os alunos',
                    'Pontualidade e organização',
                    'Capacidade de motivar e engajar a turma',
                    'Avaliações justas e coerentes',
                ]),
            ],
            [
                'type' => SectionType::Coordination,
                'title' => 'Coordenação',
                'sort_order' => 2,
                'questions' => $this->scaleQuestions('S2', [
                    'Acessibilidade da coordenação',
                    'Resolução de conflitos',
                    'Comunicação com famílias e alunos',
                    'Acompanhamento pedagógico',
                    'Apoio em situações de dificuldade',
                    'Organização das rotinas escolares',
                ]),
            ],
            [
                'type' => SectionType::Secretariat,
                'title' => 'Secretaria',
                'sort_order' => 3,
                'questions' => $this->scaleQuestions('S3', [
                    'Atendimento cordial',
                    'Agilidade na entrega de documentos',
                    'Clareza nas informações prestadas',
                    'Disponibilidade nos horários divulgados',
                    'Eficiência na resolução de demandas',
                    'Profissionalismo da equipe',
                ]),
            ],
            [
                'type' => SectionType::PhysicalStructure,
                'title' => 'Estrutura Física',
                'sort_order' => 4,
                'questions' => $this->scaleQuestions('S4', [
                    'Limpeza das salas de aula',
                    'Conservação dos banheiros',
                    'Iluminação e ventilação',
                    'Carteiras e mobiliário',
                    'Quadras e áreas esportivas',
                    'Laboratórios',
                    'Biblioteca',
                    'Pátio e áreas de convivência',
                    'Acessibilidade',
                    'Segurança',
                    'Sinalização interna',
                ]),
            ],
            [
                'type' => SectionType::Cafeteria,
                'title' => 'Cantina',
                'sort_order' => 5,
                'questions' => $this->scaleQuestions('S5', [
                    'Qualidade dos alimentos',
                    'Variedade do cardápio',
                    'Higiene do ambiente',
                    'Atendimento dos atendentes',
                    'Preço dos produtos',
                    'Tempo de espera',
                    'Opções saudáveis',
                    'Conforto do espaço',
                ]),
            ],
            [
                'type' => SectionType::SocialMedia,
                'title' => 'Redes Sociais',
                'sort_order' => 6,
                'questions' => $this->scaleQuestions('S6', [
                    'Frequência das publicações',
                    'Qualidade do conteúdo',
                    'Resposta a comentários e mensagens',
                    'Cobertura dos eventos escolares',
                    'Identidade visual',
                    'Relevância das informações',
                    'Interação com a comunidade',
                ]),
            ],
            [
                'type' => SectionType::Chaplaincy,
                'title' => 'Capelania',
                'sort_order' => 7,
                'questions' => $this->scaleQuestions('S7', [
                    'Acolhimento da equipe',
                    'Qualidade dos momentos de oração',
                    'Promoção de valores cristãos',
                    'Acompanhamento espiritual',
                    'Eventos e celebrações',
                    'Diálogo com famílias e alunos',
                ]),
            ],
            [
                'type' => SectionType::Institutional,
                'title' => 'Avaliação Institucional',
                'sort_order' => 8,
                'questions' => $this->scaleQuestions('S8', [
                    'Comprometimento com a formação integral',
                    'Coerência entre discurso e prática',
                    'Imagem da escola na comunidade',
                ]),
            ],
            [
                'type' => SectionType::NpsFinal,
                'title' => 'NPS Final',
                'description' => 'Em uma escala de 0 a 10, o quanto você recomendaria nossa escola?',
                'sort_order' => 9,
                'questions' => [
                    [
                        'code' => 'S9NPS',
                        'text' => 'O quanto você recomendaria o Colégio Pense para um amigo ou familiar?',
                        'type' => QuestionType::Scale0to10,
                        'sort_order' => 1,
                        'is_required' => true,
                    ],
                    [
                        'code' => 'S9T1',
                        'text' => 'O que mais te agrada na escola?',
                        'type' => QuestionType::FreeText,
                        'sort_order' => 2,
                        'is_required' => false,
                    ],
                    [
                        'code' => 'S9T2',
                        'text' => 'O que você gostaria que melhorasse?',
                        'type' => QuestionType::FreeText,
                        'sort_order' => 3,
                        'is_required' => false,
                    ],
                    [
                        'code' => 'S9T3',
                        'text' => 'Comentários ou sugestões adicionais',
                        'type' => QuestionType::FreeText,
                        'sort_order' => 4,
                        'is_required' => false,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  list<string>  $texts
     * @return list<array{code: string, text: string, type: QuestionType, sort_order: int, is_required: bool}>
     */
    private function scaleQuestions(string $sectionCode, array $texts): array
    {
        $questions = [];
        foreach ($texts as $index => $text) {
            $position = $index + 1;
            $questions[] = [
                'code' => sprintf('%sQ%d', $sectionCode, $position),
                'text' => $text,
                'type' => QuestionType::Scale1to5,
                'sort_order' => $position,
                'is_required' => true,
            ];
        }

        return $questions;
    }
}
