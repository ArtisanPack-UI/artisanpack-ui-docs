import { EditorContent, useEditor, type Editor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import { useEffect, useId } from 'react';

interface RichTextEditorProps {
    id?: string;
    label?: string;
    hint?: string;
    error?: string;
    required?: boolean;
    value: string;
    onValueChange: (value: string) => void;
    minHeight?: string;
    placeholder?: string;
}

interface ToolbarButtonProps {
    onClick: () => void;
    active?: boolean;
    disabled?: boolean;
    label: string;
    children: React.ReactNode;
}

function ToolbarButton({ onClick, active, disabled, label, children }: ToolbarButtonProps) {
    return (
        <button
            type="button"
            onClick={onClick}
            disabled={disabled}
            aria-label={label}
            aria-pressed={active}
            className={`btn btn-sm btn-ghost ${active ? 'btn-active' : ''}`.trim()}
        >
            {children}
        </button>
    );
}

function Toolbar({ editor }: { editor: Editor }) {
    const promptForLink = () => {
        const previous = editor.getAttributes('link').href as string | undefined;
        const url = window.prompt('Link URL', previous ?? 'https://');

        if (url === null) {
            return;
        }
        if (url === '') {
            editor.chain().focus().extendMarkRange('link').unsetLink().run();
            return;
        }
        // Allowlist safe schemes only — reject javascript:, data:, vbscript: etc.
        // The public viewer sanitizes with kses, but the editor renders raw
        // HTML inside contentEditable, so an unfiltered href could fire from
        // any code path that bypasses kses (SSR preview, raw HTML copy, etc.).
        if (!/^(https?:\/\/|mailto:|tel:|\/|#)/i.test(url.trim())) {
            window.alert('Links must start with http://, https://, mailto:, tel:, /, or #.');
            return;
        }
        editor.chain().focus().extendMarkRange('link').setLink({ href: url.trim() }).run();
    };

    return (
        <div className="flex flex-wrap items-center gap-1 border-b border-border-subtle bg-surface-muted px-2 py-1">
            <ToolbarButton
                label="Bold"
                onClick={() => editor.chain().focus().toggleBold().run()}
                active={editor.isActive('bold')}
            >
                <strong>B</strong>
            </ToolbarButton>
            <ToolbarButton
                label="Italic"
                onClick={() => editor.chain().focus().toggleItalic().run()}
                active={editor.isActive('italic')}
            >
                <em>I</em>
            </ToolbarButton>
            <ToolbarButton
                label="Strikethrough"
                onClick={() => editor.chain().focus().toggleStrike().run()}
                active={editor.isActive('strike')}
            >
                <s>S</s>
            </ToolbarButton>
            <span className="mx-1 h-4 w-px bg-border-subtle" aria-hidden />
            <ToolbarButton
                label="Heading 2"
                onClick={() => editor.chain().focus().toggleHeading({ level: 2 }).run()}
                active={editor.isActive('heading', { level: 2 })}
            >
                H2
            </ToolbarButton>
            <ToolbarButton
                label="Heading 3"
                onClick={() => editor.chain().focus().toggleHeading({ level: 3 }).run()}
                active={editor.isActive('heading', { level: 3 })}
            >
                H3
            </ToolbarButton>
            <ToolbarButton
                label="Paragraph"
                onClick={() => editor.chain().focus().setParagraph().run()}
                active={editor.isActive('paragraph')}
            >
                ¶
            </ToolbarButton>
            <span className="mx-1 h-4 w-px bg-border-subtle" aria-hidden />
            <ToolbarButton
                label="Bulleted list"
                onClick={() => editor.chain().focus().toggleBulletList().run()}
                active={editor.isActive('bulletList')}
            >
                • List
            </ToolbarButton>
            <ToolbarButton
                label="Numbered list"
                onClick={() => editor.chain().focus().toggleOrderedList().run()}
                active={editor.isActive('orderedList')}
            >
                1. List
            </ToolbarButton>
            <ToolbarButton
                label="Blockquote"
                onClick={() => editor.chain().focus().toggleBlockquote().run()}
                active={editor.isActive('blockquote')}
            >
                &ldquo;&rdquo;
            </ToolbarButton>
            <ToolbarButton
                label="Code block"
                onClick={() => editor.chain().focus().toggleCodeBlock().run()}
                active={editor.isActive('codeBlock')}
            >
                {'</>'}
            </ToolbarButton>
            <span className="mx-1 h-4 w-px bg-border-subtle" aria-hidden />
            <ToolbarButton label="Link" onClick={promptForLink} active={editor.isActive('link')}>
                🔗
            </ToolbarButton>
            <ToolbarButton
                label="Undo"
                onClick={() => editor.chain().focus().undo().run()}
                disabled={!editor.can().undo()}
            >
                ↶
            </ToolbarButton>
            <ToolbarButton
                label="Redo"
                onClick={() => editor.chain().focus().redo().run()}
                disabled={!editor.can().redo()}
            >
                ↷
            </ToolbarButton>
        </div>
    );
}

export function RichTextEditor({
    id,
    label,
    hint,
    error,
    required,
    value,
    onValueChange,
    minHeight = '320px',
    placeholder,
}: RichTextEditorProps) {
    const generatedId = useId();
    const fieldId = id ?? generatedId;
    const hintId = `${fieldId}-hint`;
    const errorId = `${fieldId}-error`;

    const editor = useEditor({
        extensions: [
            StarterKit.configure({ heading: { levels: [2, 3, 4] } }),
            Link.configure({
                openOnClick: false,
                autolink: true,
                HTMLAttributes: { rel: 'noopener noreferrer nofollow' },
            }),
        ],
        content: value,
        onUpdate: ({ editor: instance }) => {
            const html = instance.getHTML();
            onValueChange(html === '<p></p>' ? '' : html);
        },
        editorProps: {
            attributes: {
                id: fieldId,
                role: 'textbox',
                'aria-multiline': 'true',
                'aria-label': label ?? 'Content',
                'aria-invalid': error ? 'true' : 'false',
                'aria-describedby': error ? errorId : hint ? hintId : '',
                class: 'focus:outline-none px-4 py-3',
                style: `min-height: ${minHeight}`,
            },
        },
    });

    useEffect(() => {
        if (!editor) {
            return;
        }
        if (value === editor.getHTML()) {
            return;
        }
        editor.commands.setContent(value || '', { emitUpdate: false });
    }, [value, editor]);

    return (
        <div className="form-control w-full">
            {label ? (
                <label htmlFor={fieldId} className="label">
                    <span className="label-text">
                        {label}
                        {required ? <span className="ml-1 text-error">*</span> : null}
                    </span>
                </label>
            ) : null}

            <div
                className={`ap-rte overflow-hidden rounded-box border ${error ? 'border-error' : 'border-border-subtle'} bg-surface`.trim()}
                onClickCapture={(event) => {
                    // Anchor tags render as real <a href>, so the browser
                    // navigates on click even with TipTap's openOnClick: false.
                    // Capture-phase preventDefault runs before the browser's
                    // navigation, letting ProseMirror place the caret instead.
                    const anchor = (event.target as HTMLElement | null)?.closest('a');
                    if (anchor) {
                        event.preventDefault();
                    }
                }}
            >
                {editor ? <Toolbar editor={editor} /> : null}
                <EditorContent editor={editor} placeholder={placeholder} />
            </div>

            {error ? (
                <p id={errorId} className="mt-1 text-sm text-error">
                    {error}
                </p>
            ) : hint ? (
                <p id={hintId} className="mt-1 text-sm text-text-muted">
                    {hint}
                </p>
            ) : null}
        </div>
    );
}
