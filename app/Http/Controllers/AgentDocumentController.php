<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentDocument;
use App\Services\AgentDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;
use RuntimeException;

class AgentDocumentController extends Controller
{
    public function __construct(
        private readonly AgentDocumentService $documentService,
    ) {}

    public function store(Request $request, Agent $agent, string $type): RedirectResponse
    {
        if ($agent->date_suppression !== null) {
            abort(404);
        }

        try {
            $request->validate(
                $this->documentService->validationRulesForType($type)
            );
        } catch (InvalidArgumentException $e) {
            abort(404);
        }

        try {
            $this->documentService->upload(
                $agent,
                $type,
                $request->file('document'),
                $request->user(),
            );
        } catch (RuntimeException|InvalidArgumentException $e) {
            return back()
                ->withErrors(['document_'.$type => $e->getMessage()]);
        }

        $label = AgentDocument::types()[$type] ?? 'Document';

        return redirect()
            ->route('agents.show', $agent)
            ->with('success', $label.' enregistré avec succès.')
            ->withFragment('agent-documents');
    }

    public function show(Agent $agent, string $type): Response
    {
        if ($agent->date_suppression !== null) {
            abort(404);
        }

        $document = $this->documentService->getDocument($agent, $type);

        if ($document === null) {
            abort(404);
        }

        $content = $this->documentService->getObjectContents($document);

        if ($content === null) {
            abort(404);
        }

        return response($content, 200, [
            'Content-Type' => $document->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.addslashes($document->original_name ?: $document->type).'"',
            'Cache-Control' => 'private, no-cache, must-revalidate',
            'ETag' => '"'.md5($document->object_key).'"',
        ]);
    }
}
