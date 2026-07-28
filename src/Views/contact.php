<?php
// src/Views/contact.php
ob_start();
?>

<section class="contact-hero">
    <h1>Nous contacter</h1>
    <p>Une question, un menu sur mesure ou un événement à organiser ? Écrivez-nous, nous vous répondons rapidement.</p>
</section>

<section class="contact-page">
    <div class="contact-layout">

        <div class="contact-form-col">
            <?php if ($succes): ?>
                <div class="alert alert-succes" role="status" aria-live="polite">
                    <strong>Message envoyé !</strong> Nous avons bien reçu votre demande et vous répondrons très bientôt.
                </div>
            <?php endif; ?>

            <?php if (!empty($erreurs)): ?>
                <div class="alert alert-erreur" role="alert">
                    <strong>Corrigez les erreurs suivantes :</strong>
                    <ul>
                        <?php foreach ($erreurs as $erreur): ?>
                            <li><?= htmlspecialchars($erreur) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= BASE_URL ?>/contact" class="contact-form">
                <?= Csrf::champ() ?>

                <div class="champ">
                    <label for="titre">Titre de votre demande <span class="required">*</span></label>
                    <input
                        type="text"
                        id="titre"
                        name="titre"
                        value="<?= htmlspecialchars($old['titre']) ?>"
                        placeholder="Ex : Question sur un menu, demande spéciale…"
                        required
                        maxlength="255"
                    >
                    <small class="aide">Soyez concis et explicite</small>
                </div>

                <div class="champ">
                    <label for="mail">Votre adresse mail <span class="required">*</span></label>
                    <input
                        type="email"
                        id="mail"
                        name="mail"
                        value="<?= htmlspecialchars($old['mail']) ?>"
                        placeholder="votre.email@exemple.com"
                        required
                    >
                    <small class="aide">Nous vous répondrons à cette adresse</small>
                </div>

                <div class="champ">
                    <label for="description">Votre message <span class="required">*</span></label>
                    <textarea
                        id="description"
                        name="description"
                        placeholder="Détaillez votre demande…"
                        rows="6"
                        minlength="10"
                        maxlength="2000"
                        required
                    ><?= htmlspecialchars($old['description']) ?></textarea>
                    <small class="aide">Minimum 10 caractères, maximum 2000</small>
                </div>

                <div class="contact-actions">
                    <button type="submit" class="btn btn-submit">Envoyer mon message</button>
                    <?= ViewHelper::btnNav(BASE_URL . '/', 'Annuler', 'btn btn-outline') ?>
                </div>
            </form>
        </div>

        <aside class="contact-aside">
            <h2>Autres façons de nous joindre</h2>

            <div class="contact-card">
                <span class="contact-icon" aria-hidden="true">✉️</span>
                <h3>Par e-mail</h3>
                <p>Écrivez-nous directement, nous vous répondrons dans les meilleurs délais.</p>
                <p class="contact-detail"><a href="mailto:<?= htmlspecialchars($emailContact) ?>"><?= htmlspecialchars($emailContact) ?></a></p>
            </div>

            <div class="contact-card">
                <span class="contact-icon" aria-hidden="true">📞</span>
                <h3>Par téléphone</h3>
                <p>Nous serons ravis d'échanger avec vous directement lors de nos horaires d'ouverture.</p>
                <p class="contact-detail">
                    <a href="tel:+33615239439">06 15 23 94 39</a><br>
                    Du lundi au dimanche<br>
                    11h00 – 14h30 · 18h30 – 23h00
                </p>
            </div>

            <div class="contact-card">
                <span class="contact-icon" aria-hidden="true">📍</span>
                <h3>En personne</h3>
                <p>Rencontrons-nous à Bordeaux pour discuter de votre événement et imaginer ensemble votre menu.</p>
                <p class="contact-detail">
                    12 quai des Chartrons<br>
                    33000 Bordeaux
                </p>
                <p class="contact-detail">Traiteur événementiel depuis 25 ans à Bordeaux</p>
            </div>
        </aside>

    </div>
</section>

<?php
$contenuPage = ob_get_clean();
require_once __DIR__ . '/layout.php';
