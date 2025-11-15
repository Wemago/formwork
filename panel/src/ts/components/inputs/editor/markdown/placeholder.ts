import { Decoration, DecorationSet } from "prosemirror-view";
import { EditorState, Plugin } from "prosemirror-state";

export function placeholderPlugin(text: string) {
    return new Plugin({
        props: {
            decorations(state: EditorState) {
                const isEmpty = state.doc.childCount === 1 && state.doc?.firstChild?.isTextblock && state.doc?.firstChild.content.size === 0;

                if (!isEmpty) {
                    return null;
                }

                const placeholder = document.createElement("span");
                placeholder.className = "pm-placeholder";
                placeholder.textContent = text;

                return DecorationSet.create(state.doc, [Decoration.widget(1, placeholder, { side: 1 })]);
            },
        },
    });
}
