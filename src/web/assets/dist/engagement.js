(function (window, document) {
  'use strict';

  const initialized = new WeakMap();

  const number = (value, fallback = 0) => {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : fallback;
  };

  const format = (template, values) => String(template || '').replace(/\{([a-zA-Z]+)\}/g, (match, token) => {
    return Object.prototype.hasOwnProperty.call(values, token) ? String(values[token] ?? '') : match;
  });

  const rgb = (hex) => {
    const match = String(hex || '').trim().match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);
    if (!match) return null;
    const value = match[1].length === 3 ? match[1].split('').map((part) => part + part).join('') : match[1];
    return {
      r: parseInt(value.slice(0, 2), 16),
      g: parseInt(value.slice(2, 4), 16),
      b: parseInt(value.slice(4, 6), 16),
    };
  };

  const contrast = (background) => {
    const color = rgb(background);
    if (!color) return '';
    return (0.299 * color.r) + (0.587 * color.g) + (0.114 * color.b) < 140 ? '#fff' : '#000';
  };

  const announce = (element, message) => {
    if (!element) return;
    element.textContent = '';
    window.requestAnimationFrame(() => { element.textContent = message || ''; });
  };

  const parseConfig = (root) => {
    try {
      return JSON.parse(root.getAttribute('data-engagement-config') || '{}');
    } catch (error) {
      return null;
    }
  };

  const createWidget = (root) => {
    if (initialized.has(root)) return initialized.get(root);

    const config = parseConfig(root);
    if (!config || !config.type) return null;

    const listeners = [];
    let destroyed = false;
    let state = {
      isLoggedIn: Boolean(config.isLoggedIn),
      userRating: number(config.userRating),
      average: number(config.average),
      voteCount: number(config.voteCount),
      isFavorited: Boolean(config.isFavorited),
      favoriteCount: number(config.favoriteCount),
      userVote: number(config.userVote),
      likeCount: number(config.likeCount),
      dislikeCount: number(config.dislikeCount),
    };

    const on = (element, name, handler) => {
      if (!element) return;
      element.addEventListener(name, handler);
      listeners.push(() => element.removeEventListener(name, handler));
    };

    const guestMessage = (replaceTarget) => {
      if (state.isLoggedIn || config.guestAllowed) return false;
      if (config.guestInteractionMode === 'redirect') {
        window.location.assign(config.loginUrl || '/login');
        return true;
      }
      if (!root.querySelector('[data-guest-inline-message]')) {
        const message = document.createElement('div');
        message.dataset.guestInlineMessage = '1';
        message.className = 'engagement-guest-message';
        message.setAttribute('role', 'alert');
        message.innerHTML = config.guestInteractionMessageHtml || 'Please log in or register to interact.';
        if (replaceTarget) replaceTarget.replaceWith(message);
        else root.appendChild(message);
      }
      return true;
    };

    // Called only from a permitted interaction, never during initialization.
    const freshSession = async () => {
      const response = await fetch(config.csrfUrl, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
      });
      if (!response.ok) throw new Error('Could not refresh the session.');
      const session = await response.json();
      state.isLoggedIn = !session.isGuest;
      return session;
    };

    const post = async (payload, replaceTarget) => {
      if (guestMessage(replaceTarget)) return null;
      const session = await freshSession();
      if (!state.isLoggedIn && !config.guestAllowed) {
        guestMessage(replaceTarget);
        return null;
      }
      const headers = { 'Content-Type': 'application/json', Accept: 'application/json' };
      if (session.csrfTokenValue) headers['X-CSRF-Token'] = session.csrfTokenValue;
      const response = await fetch(config.actionUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers,
        body: JSON.stringify(Object.assign({
          elementId: number(config.elementId),
          fieldId: number(config.fieldId),
          siteId: number(config.siteId),
        }, payload)),
      });
      if (!response.ok) throw new Error('The interaction was not accepted.');
      return response.json();
    };

    let submit = async () => null;
    let refresh = () => {};

    if (config.type === 'rating') {
      const buttons = Array.from(root.querySelectorAll('[data-rating-value]'));
      const heading = root.querySelector('[data-rating-heading]');
      const votes = root.querySelector('[data-rating-votes]');
      const user = root.querySelector('[data-rating-user]');
      const fill = root.querySelector('[data-overall-fill]');
      const overall = root.querySelector('[data-overall-wrap]');
      const status = root.querySelector('[data-rating-status]');
      const info = root.querySelector('[data-rating-info]');
      const infoToggle = root.querySelector('[data-rating-info-toggle]');
      const infoPopup = root.querySelector('[data-rating-info-popup]');
      const scale = number(config.scale, 5);
      const values = () => ({
        userRating: state.userRating,
        votes: state.voteCount,
        average: state.average,
        roundedAverage: Math.round(state.average * 4) / 4,
        scale,
        fieldName: config.fieldName || '',
        elementId: config.elementId,
      });
      refresh = () => {
        if (heading) {
          heading.textContent = format(config.headingText, values());
          heading.style.display = heading.textContent ? '' : 'none';
        }
        if (votes) {
          votes.textContent = `${state.voteCount} total ratings`;
          votes.style.display = '';
        }
        if (user) {
          user.textContent = format(state.userRating ? config.yourRatingText : config.clickToRateText, values());
          user.style.display = user.textContent ? '' : 'none';
        }
        if (fill) fill.style.width = scale > 0 ? `${(Math.round(state.average * 4) / 4 / scale) * 100}%` : '0%';
        buttons.forEach((button) => {
          const value = number(button.dataset.ratingValue);
          button.setAttribute('aria-checked', state.userRating === value ? 'true' : 'false');
          button.setAttribute('tabindex', (state.userRating ? state.userRating === value : value === 1) ? '0' : '-1');
        });
      };
      submit = async (value) => {
        const rating = number(value);
        if (rating < 1 || rating > scale) return null;
        try {
          const data = await post({ rating }, overall);
          if (!data) return null;
          state.userRating = number(data.vote?.rating, rating);
          state.average = number(data.aggregate?.average, state.average);
          state.voteCount = number(data.aggregate?.voteCount, state.voteCount);
          refresh();
          announce(status, config.updatedText);
          return data;
        } catch (error) {
          announce(status, config.errorText);
          return null;
        }
      };
      buttons.forEach((button) => {
        on(button, 'click', () => submit(button.dataset.ratingValue));
        on(button, 'keydown', (event) => {
          const current = number(button.dataset.ratingValue);
          let next = current;
          if (event.key === 'ArrowRight' || event.key === 'ArrowUp') next = current >= scale ? 1 : current + 1;
          else if (event.key === 'ArrowLeft' || event.key === 'ArrowDown') next = current <= 1 ? scale : current - 1;
          else if (event.key === 'Home') next = 1;
          else if (event.key === 'End') next = scale;
          else if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); submit(current); return; }
          else return;
          event.preventDefault();
          root.querySelector(`[data-rating-value="${next}"]`)?.focus();
        });
      });
      if (infoToggle && infoPopup) {
        on(infoToggle, 'click', (event) => {
          event.preventDefault();
          event.stopPropagation();
          const isOpen = infoToggle.getAttribute('aria-expanded') === 'true';
          infoToggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
          infoPopup.hidden = isOpen;
        });
        on(document, 'click', (event) => {
          if (event.target instanceof Element && info?.contains(event.target)) return;
          infoToggle.setAttribute('aria-expanded', 'false');
          infoPopup.hidden = true;
        });
      }
    }

    if (config.type === 'favorite') {
      const button = root.querySelector('[data-favorite-btn]');
      const heading = root.querySelector('[data-favorite-heading]');
      const label = root.querySelector('[data-favorite-label]');
      const icon = root.querySelector('[data-favorite-icon]');
      const status = root.querySelector('[data-favorite-status]');
      const widget = root.querySelector('[data-engagement-favorite]');
      const values = () => ({
        favorites: state.favoriteCount,
        favoriteCount: state.favoriteCount,
        fieldName: config.fieldName || '',
        elementId: config.elementId,
        isFavorited: state.isFavorited ? 1 : 0,
      });
      refresh = () => {
        if (heading) {
          heading.textContent = format(config.headingText, values());
          heading.style.display = heading.textContent ? '' : 'none';
        }
        if (label) label.textContent = format(state.isFavorited ? config.unfavoriteText : config.favoriteText, values());
        if (button) {
          const background = state.isFavorited ? (config.afterFavoriteColor || '#fff') : (config.beforeFavoriteColor || '#fff');
          button.style.background = background;
          button.style.color = contrast(background);
          button.setAttribute('aria-pressed', state.isFavorited ? 'true' : 'false');
          button.setAttribute('aria-label', label?.textContent?.trim() || 'Toggle favorite');
        }
        if (icon) {
          const mode = widget?.dataset.iconMode || 'heart';
          if (mode === 'star') icon.textContent = state.isFavorited ? '★' : '☆';
          else if (mode === 'heart') icon.textContent = state.isFavorited ? '♥' : '♡';
          const outline = icon.querySelector('[data-thumb-outline]');
          const filled = icon.querySelector('[data-thumb-fill]');
          if (outline) outline.style.opacity = state.isFavorited ? '0' : '1';
          if (filled) filled.style.opacity = state.isFavorited ? '1' : '0';
        }
      };
      submit = async () => {
        try {
          const data = await post({}, button);
          if (!data) return null;
          state.isFavorited = Boolean(data.entry?.isFavorited);
          state.favoriteCount = number(data.aggregate?.favoriteCount, state.favoriteCount);
          refresh();
          announce(status, config.updatedText);
          return data;
        } catch (error) {
          announce(status, config.errorText);
          return null;
        }
      };
      on(button, 'click', submit);
    }

    if (config.type === 'likes') {
      const like = root.querySelector('[data-like-btn="1"]');
      const dislike = root.querySelector('[data-like-btn="-1"]');
      const heading = root.querySelector('[data-likes-heading]');
      const likeLabel = root.querySelector('[data-like-label]');
      const dislikeLabel = root.querySelector('[data-dislike-label]');
      const status = root.querySelector('[data-likes-status]');
      const controls = root.querySelector('.engagement-likes-control');
      const values = () => ({ likes: state.likeCount, dislikes: state.dislikeCount, userVote: state.userVote, fieldName: config.fieldName || '', elementId: config.elementId });
      refresh = () => {
        if (heading) { heading.textContent = format(config.headingText, values()); heading.style.display = heading.textContent ? '' : 'none'; }
        if (likeLabel) likeLabel.textContent = format(config.likeText, values());
        if (dislikeLabel) dislikeLabel.textContent = format(config.dislikeText, values());
        [[like, 1, config.beforeLikeColor, config.afterLikeColor], [dislike, -1, config.beforeDislikeColor, config.afterDislikeColor]].forEach(([button, value, before, after]) => {
          if (!button) return;
          const background = state.userVote === value ? (after || '#fff') : (before || '#fff');
          button.style.background = background;
          button.style.color = contrast(background);
          button.setAttribute('aria-pressed', state.userVote === value ? 'true' : 'false');
        });
      };
      submit = async (value) => {
        try {
          const data = await post({ value: number(value) }, controls);
          if (!data) return null;
          state.userVote = number(data.vote?.value);
          state.likeCount = number(data.aggregate?.likeCount, state.likeCount);
          state.dislikeCount = number(data.aggregate?.dislikeCount, state.dislikeCount);
          refresh();
          announce(status, config.updatedText);
          return data;
        } catch (error) {
          announce(status, config.errorText);
          return null;
        }
      };
      on(like, 'click', () => submit(1));
      on(dislike, 'click', () => submit(-1));
    }

    const api = {
      root,
      config: Object.freeze(Object.assign({}, config)),
      submit: (value) => submit(value),
      refresh: () => refresh(),
      getState: () => Object.assign({}, state),
      destroy: () => {
        if (destroyed) return;
        destroyed = true;
        listeners.splice(0).forEach((remove) => remove());
        initialized.delete(root);
      },
    };
    initialized.set(root, api);
    refresh();
    root.dispatchEvent(new CustomEvent('engagement:ready', {
      bubbles: true,
      detail: { root, widget: api.config, data: api.getState(), api },
    }));
    return api;
  };

  const init = (root = document) => {
    if (root instanceof Element && root.matches('[data-engagement-deferred]')) return createWidget(root);
    if (!root || !root.querySelectorAll) return [];
    return Array.from(root.querySelectorAll('[data-engagement-deferred]')).map(createWidget).filter(Boolean);
  };

  window.Engagement = Object.assign(window.Engagement || {}, { init });

  const boot = () => init(document);
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true });
  else boot();

  document.addEventListener('afterBlitzInject', (event) => init(event.target || document));
})(window, document);
