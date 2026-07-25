function addCopyButtons() {
    const codeContainers = document.querySelectorAll('.code-block-container');

    codeContainers.forEach((container, index) => {
        if (container.querySelector('.copy-code-button')) {
            return;
        }

        const codeBlock = container.querySelector('code');
        if (!codeBlock) {
            return;
        }

        const button = document.createElement('button');
        button.className = 'copy-code-button';
        button.type = 'button'; // Prevent form submission
        button.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
        `;
        button.setAttribute('aria-label', 'Copy code to clipboard');
        button.setAttribute('title', 'Copy code');

        // Add click event
        button.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();

            const code = codeBlock.textContent;
            let copySuccessful = false;

            if (navigator.clipboard && navigator.clipboard.writeText) {
                try {
                    await navigator.clipboard.writeText(code);
                    copySuccessful = true;
                } catch (err) {
                    console.error('Clipboard API failed:', err);
                }
            }

            // Fallback for HTTP or older browsers
            if (!copySuccessful) {
                try {
                    const textarea = document.createElement('textarea');
                    textarea.value = code;
                    textarea.style.position = 'fixed';
                    textarea.style.left = '-9999px';
                    textarea.style.top = '0';
                    document.body.appendChild(textarea);
                    textarea.focus();
                    textarea.select();

                    const successful = document.execCommand('copy');
                    document.body.removeChild(textarea);

                    if (successful) {
                        copySuccessful = true;
                    } else {
                        console.error('Fallback copy command returned false');
                    }
                } catch (fallbackErr) {
                    console.error('Fallback copy failed:', fallbackErr);
                }
            }

            // Show visual feedback if copy was successful
            if (copySuccessful) {
                button.classList.add('copied');
                button.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                `;
                button.setAttribute('title', 'Copied!');

                // Reset after 2 seconds
                setTimeout(() => {
                    button.classList.remove('copied');
                    button.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    `;
                    button.setAttribute('title', 'Copy code');
                }, 2000);
            } else {
                console.error('All copy methods failed');
            }
        });

        container.appendChild(button);
    });
}

function initializeCodeBlocks() {
    if (typeof Prism !== 'undefined') {
        Prism.highlightAll();
    }

    setTimeout(() => {
        addCopyButtons();
    }, 100);
}

document.addEventListener('DOMContentLoaded', initializeCodeBlocks);

document.addEventListener('inertia:navigated', () => {
    initializeCodeBlocks();
});