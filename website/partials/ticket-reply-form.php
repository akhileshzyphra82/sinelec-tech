<form class="td-reply-form" id="tdReplyForm">
  <label class="td-reply-label" for="tdReplyBody">Reply</label>
  <textarea class="td-reply-textarea" id="tdReplyBody" name="body"
            placeholder="Describe your issue or add more information…" rows="4"></textarea>

  <div class="td-reply-footer">
    <div class="td-att-zone">
      <label class="td-att-btn" for="tdAttInput">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
        Attach Files
      </label>
      <input type="file" id="tdAttInput" style="display:none" multiple
             accept=".jpg,.jpeg,.png,.webp,.gif,.pdf">
      <div id="tdAttPreview" class="td-att-preview"></div>
    </div>
    <button type="submit" class="td-send-btn" id="tdSendBtn">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
      Send Reply
    </button>
  </div>

  <div class="td-reply-error" id="tdReplyError"></div>
  <div class="td-reply-success" id="tdReplySuccess">Message sent successfully.</div>
</form>
