<?php if (isset($this->segmentGroups)): ?>
    <?php
    /** @var \CustomerManagementFramework\Model\CustomerSegmentInterface|\Pimcore\Model\Element\ElementInterface $prefilteredSegment */
    $prefilteredSegment = $this->prefilteredSegment;
    ?>

    <fieldset>
        <legend>
            Segments
        </legend>

        <div class="row">

            <?php
            /** @var \Pimcore\Model\Object\CustomerSegmentGroup $segmentGroup */
            foreach ($this->segmentGroups as $segmentGroup): ?>

                <?php
                $readonly = false;
                if (null !== $prefilteredSegment && $prefilteredSegment->getGroup()->getId() === $segmentGroup->getId()) {
                    $readonly = true;
                }
                ?>

                <div class="col-md-6 col-xs-12">
                    <div class="form-group">
                        <label for="form-filter-segment-<?= $segmentGroup->getId() ?>"><?= $segmentGroup->getName() ?></label>
                        <select id="form-filter-segment-<?= $segmentGroup->getId() ?>" name="filter[segments][<?= $segmentGroup->getId() ?>][]" class="form-control plugin-select2" multiple="multiple" <?= $readonly ? 'readonly disabled' : '' ?> data-placeholder="<?= $segmentGroup->getName() ?>">

                            <?php if (null !== $prefilteredSegment && $readonly): ?>

                                <option value="<?= $prefilteredSegment->getId() ?>" selected>
                                    <?= $prefilteredSegment->getName() ?>
                                </option>

                            <?php else: ?>

                                <?php
                                $segments = \CustomerManagementFramework\Factory::getInstance()
                                    ->getSegmentManager()
                                    ->getSegmentsFromSegmentGroup($segmentGroup);

                                /** @var \CustomerManagementFramework\Model\CustomerSegmentInterface|\Pimcore\Model\Element\ElementInterface $segment */
                                foreach ($segments as $segment): ?>

                                    <option value="<?= $segment->getId() ?>" <?= $this->formFilterSelectedState($segmentGroup->getId(), $segment->getId(), true, ['filters', 'segments']) ?>>
                                        <?= $segment->getName() ?>
                                    </option>

                                <?php endforeach; ?>

                            <?php endif; ?>
                        </select>
                    </div>
                </div>

            <?php endforeach; ?>

        </div>
    </fieldset>
<?php endif; ?>
