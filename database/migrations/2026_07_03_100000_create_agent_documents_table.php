<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agent_documents')) {
            return;
        }

        Schema::create('agent_documents', function (Blueprint $table) {
            $table->increments('id_document');
            $table->integer('id_agent');
            $table->string('type', 30);
            $table->string('object_key', 500);
            $table->string('original_name', 255)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->unique(['id_agent', 'type']);
            $table->index('id_agent');

            $table->foreign('id_agent')
                ->references('id_agent')
                ->on('agents')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_documents');
    }
};
